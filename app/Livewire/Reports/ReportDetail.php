<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Services\Waqty\ReportService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Drill-down report screen (/reports/{category}/{report}). Reads tabular rows
 * from {@see ReportService::reportTable()} and, when the API is unreachable,
 * falls back to Arabic sample rows — same graceful pattern as Reports/Overview.
 * Columns adapt generically to the row keys (money / number / percent / status /
 * text), so the same view serves any drill-down.
 */
#[Layout('components.layouts.app')]
#[Title('Report — Waqty')]
class ReportDetail extends Component
{
    public string $category = '';

    public string $report = '';

    /** 30d | 3m | 6m */
    public string $range = '3m';

    public string $branch = 'all';

    public string $search = '';

    public string $sortField = '';

    /** asc | desc */
    public string $sortDir = 'asc';

    private bool $fallbackUsed = false;

    public function mount(string $category, string $report): void
    {
        $this->category = $category;
        $this->report = $report;
    }

    public function updatedRange(): void
    {
        $this->refreshData();
    }

    public function updatedBranch(): void
    {
        $this->refreshData();
    }

    private function refreshData(): void
    {
        unset($this->rows);
        $this->fallbackUsed = false;
        $this->dispatch('report-detail-charts', options: $this->chartOptions());
    }

    /**
     * Raw report rows (each an associative array). Arabic fallback on failure.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function rows(): array
    {
        try {
            $rows = app(ReportService::class)->reportTable($this->category, $this->report, $this->filters());

            return array_values(array_filter($rows, 'is_array'));
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;

            return $this->fallbackRows();
        }
    }

    public function usingFallback(): bool
    {
        $this->rows;

        return $this->fallbackUsed;
    }

    /**
     * Column metadata derived from the first row's keys.
     *
     * @return array<int, array{key:string, label:string, type:string, numeric:bool}>
     */
    #[Computed]
    public function columns(): array
    {
        $rows = $this->rows;
        if ($rows === []) {
            return [];
        }

        return array_map(function (string $key) {
            $type = $this->columnType($key);

            return [
                'key' => $key,
                'label' => $this->columnLabel($key),
                'type' => $type,
                'numeric' => in_array($type, ['money', 'number', 'percent'], true),
            ];
        }, array_keys($rows[0]));
    }

    /**
     * Search-filtered, sorted rows for the table.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function displayRows(): array
    {
        $rows = $this->rows;

        $search = trim(mb_strtolower($this->search));
        if ($search !== '') {
            $textKeys = array_map(fn ($c) => $c['key'], array_filter($this->columns, fn ($c) => in_array($c['type'], ['text', 'status'], true)));
            $rows = array_values(array_filter($rows, function ($row) use ($search, $textKeys) {
                foreach ($textKeys as $key) {
                    if (str_contains(mb_strtolower((string) ($row[$key] ?? '')), $search)) {
                        return true;
                    }
                }

                return false;
            }));
        }

        if ($this->sortField !== '') {
            $field = $this->sortField;
            $numeric = in_array($this->columnType($field), ['money', 'number', 'percent'], true);
            usort($rows, function ($a, $b) use ($field, $numeric) {
                $av = $a[$field] ?? null;
                $bv = $b[$field] ?? null;

                return $numeric
                    ? (float) $av <=> (float) $bv
                    : strcmp((string) $av, (string) $bv);
            });
            if ($this->sortDir === 'desc') {
                $rows = array_reverse($rows);
            }
        }

        return array_values($rows);
    }

    public function sort(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDir = 'asc';
        }
        unset($this->displayRows);
    }

    /**
     * KPI strip built generically from the numeric columns present.
     *
     * @return array<int, array{label:string, value:string, icon:string, iconClass:string}>
     */
    #[Computed]
    public function kpis(): array
    {
        $rows = $this->rows;
        $cards = [];

        $moneyKey = $this->firstKeyOfType('money');
        $numberKey = $this->firstKeyOfType('number');

        if ($moneyKey !== null) {
            $total = array_sum(array_map(fn ($r) => (int) ($r[$moneyKey] ?? 0), $rows));
            $cards[] = ['label' => __('reports.kpiTotalRevenue'), 'value' => Money::compact($total), 'icon' => 'wallet', 'iconClass' => 'bg-primary-100 text-primary-600'];
        }

        if ($numberKey !== null) {
            $sum = array_sum(array_map(fn ($r) => (int) ($r[$numberKey] ?? 0), $rows));
            $cards[] = ['label' => $this->columnLabel($numberKey), 'value' => number_format($sum), 'icon' => 'calendar-check', 'iconClass' => 'bg-info-light text-info'];
        }

        $cards[] = ['label' => __('reports.kpiRecords'), 'value' => number_format(count($rows)), 'icon' => 'bar-chart-3', 'iconClass' => 'bg-purple-100 text-purple-600'];

        if ($moneyKey !== null && $rows !== []) {
            $avg = (int) round(array_sum(array_map(fn ($r) => (int) ($r[$moneyKey] ?? 0), $rows)) / count($rows));
            $cards[] = ['label' => __('reports.kpiAverage'), 'value' => Money::compact($avg), 'icon' => 'trending-up', 'iconClass' => 'bg-success-light text-success'];
        }

        return $cards;
    }

    public function hasChart(): bool
    {
        return $this->labelKey() !== null && $this->valueKey() !== null && $this->rows !== [];
    }

    /** Horizontal bar of the value column by the label column. @return array<string, mixed> */
    public function chartOptions(): array
    {
        $rows = $this->rows;
        $labelKey = $this->labelKey();
        $valueKey = $this->valueKey();

        if ($labelKey === null || $valueKey === null || $rows === []) {
            return $this->emptyChart();
        }

        $slice = array_slice($rows, 0, 12);
        $isMoney = $this->columnType($valueKey) === 'money';
        $categories = array_map(fn ($r) => (string) ($r[$labelKey] ?? '—'), $slice);
        $data = array_map(
            fn ($r) => $isMoney ? round(((int) ($r[$valueKey] ?? 0)) / 100, 2) : (float) ($r[$valueKey] ?? 0),
            $slice,
        );

        return [
            'chart' => ['type' => 'bar', 'height' => 320, 'fontFamily' => 'inherit', 'foreColor' => '#94a3b8', 'toolbar' => ['show' => false]],
            'series' => [['name' => $this->columnLabel($valueKey), 'data' => array_values($data)]],
            'xaxis' => ['categories' => array_values($categories)],
            'colors' => ['#00b166'],
            'plotOptions' => ['bar' => ['horizontal' => true, 'borderRadius' => 4, 'barHeight' => '55%']],
            'dataLabels' => ['enabled' => false],
            'grid' => ['borderColor' => 'rgba(148,163,184,0.15)', 'strokeDashArray' => 4],
            'noData' => ['text' => __('common.noData')],
        ];
    }

    /** CSV/PDF export -> notify with the returned (or mocked) URL. */
    public function export(string $format): void
    {
        $format = in_array($format, ['csv', 'pdf'], true) ? $format : 'csv';

        try {
            $result = app(ReportService::class)->export($this->category, $this->report, [...$this->filters(), 'format' => $format]);
            $url = is_string($result['url'] ?? null) ? $result['url'] : null;
        } catch (WaqtyApiException) {
            $url = null;
        }

        $url ??= "/exports/{$this->category}-{$this->report}-".Carbon::now()->format('Ymd').".{$format}";

        $this->dispatch('notify', type: 'success', message: __('reports.exportReady', ['url' => $url]));
    }

    /** @return array<string, string> */
    public function branchOptions(): array
    {
        return [
            'all' => __('reports.allBranches'),
            'downtown' => 'وسط البلد',
            'new-cairo' => 'القاهرة الجديدة',
            'mall' => 'مول العرب',
        ];
    }

    /** @return array{from_date:string, to_date:string, branch_uuid?:string} */
    private function filters(): array
    {
        $days = match ($this->range) {
            '30d' => 30,
            '6m' => 180,
            default => 90,
        };

        $filters = [
            'from_date' => Carbon::today()->subDays($days - 1)->toDateString(),
            'to_date' => Carbon::today()->toDateString(),
        ];

        if ($this->branch !== 'all') {
            $filters['branch_uuid'] = $this->branch;
        }

        return $filters;
    }

    private function firstKeyOfType(string $type): ?string
    {
        foreach ($this->columns as $column) {
            if ($column['type'] === $type) {
                return $column['key'];
            }
        }

        return null;
    }

    private function labelKey(): ?string
    {
        return $this->firstKeyOfType('text');
    }

    private function valueKey(): ?string
    {
        return $this->firstKeyOfType('money') ?? $this->firstKeyOfType('number');
    }

    private function columnType(string $key): string
    {
        $k = mb_strtolower($key);

        foreach (['revenue', 'amount', 'total', 'sales', 'net', 'paid', 'price', 'expense', 'cash', 'value'] as $needle) {
            if (str_contains($k, $needle)) {
                return 'money';
            }
        }
        if (str_contains($k, 'status') || str_contains($k, 'state')) {
            return 'status';
        }
        foreach (['share', 'percent', 'rate', 'growth', 'ratio'] as $needle) {
            if (str_contains($k, $needle)) {
                return 'percent';
            }
        }
        foreach (['bookings', 'count', 'clients', 'orders', 'quantity', 'qty', 'visits', 'sessions', 'number'] as $needle) {
            if (str_contains($k, $needle)) {
                return 'number';
            }
        }

        return 'text';
    }

    private function columnLabel(string $key): string
    {
        $map = [
            'name' => 'reports.colItem',
            'label' => 'reports.colItem',
            'revenue' => 'reports.revenue',
            'bookings' => 'reports.kpiBookings',
            'clients' => 'reports.tabClients',
            'share' => 'reports.colShare',
        ];

        if (isset($map[$key])) {
            return __($map[$key]);
        }

        $translated = __('reports.col.'.$key);

        return $translated === 'reports.col.'.$key ? Str::headline($key) : $translated;
    }

    /** Humanised report title (category-slug values stay English by convention). */
    public function reportTitle(): string
    {
        return Str::headline($this->report);
    }

    public function categoryTitle(): string
    {
        return Str::headline($this->category);
    }

    public function render()
    {
        return view('livewire.reports.detail');
    }

    /** @return array<string, mixed> */
    private function emptyChart(): array
    {
        return [
            'chart' => ['type' => 'bar', 'height' => 320, 'fontFamily' => 'inherit', 'foreColor' => '#94a3b8', 'toolbar' => ['show' => false]],
            'series' => [['name' => '', 'data' => []]],
            'noData' => ['text' => __('common.noData')],
        ];
    }

    /**
     * Arabic sample rows. Names adapt to the drill-down so the demo feels real;
     * shape stays generic: name (text) + revenue (money) + bookings (number) + share (%).
     *
     * @return array<int, array<string, mixed>>
     */
    private function fallbackRows(): array
    {
        $names = $this->fallbackNames();

        $rows = [];
        foreach (array_values($names) as $i => $name) {
            $revenue = max(1_800_000 - $i * 260_000 + ($i % 2 ? 120_000 : 40_000), 180_000);
            $bookings = max(340 - $i * 46 + ($i % 3) * 18, 24);
            $rows[] = ['name' => $name, 'revenue' => $revenue, 'bookings' => $bookings];
        }

        $total = array_sum(array_column($rows, 'revenue')) ?: 1;

        return array_map(fn ($r) => [...$r, 'share' => round($r['revenue'] / $total * 100, 1)], $rows);
    }

    /** @return array<int, string> */
    private function fallbackNames(): array
    {
        $slug = mb_strtolower($this->category.' '.$this->report);

        return match (true) {
            str_contains($slug, 'branch') => ['وسط البلد', 'القاهرة الجديدة', 'مول العرب', 'المعادي', 'الزمالك'],
            str_contains($slug, 'employee') || str_contains($slug, 'staff') => ['خالد حسن', 'ياسمين فاروق', 'د. سارة أحمد', 'منى عادل', 'طارق سامي'],
            str_contains($slug, 'client') || str_contains($slug, 'customer') => ['أحمد محمود', 'ليلى إبراهيم', 'عمر خالد', 'هبة سمير', 'كريم فؤاد'],
            default => ['خدمات الشعر', 'العناية بالبشرة', 'الأظافر', 'المكياج', 'الليزر', 'المساج'],
        };
    }
}
