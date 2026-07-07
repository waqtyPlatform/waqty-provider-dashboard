<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Data\Waqty\ProviderRevenueData;
use App\Data\Waqty\ReportSeriesData;
use App\Services\Waqty\ReportService;
use App\Services\Waqty\WaqtyApiException;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Reports — Waqty')]
class Overview extends Component
{
    /** 30d | 3m | 6m */
    public string $range = '3m';

    private bool $fallbackUsed = false;

    public function updatedRange(): void
    {
        unset($this->report, $this->revenue);
        $this->fallbackUsed = false;
        $this->dispatch('reports-charts', line: $this->revenueLineOptions(), branch: $this->branchBarOptions(), employee: $this->employeeBarOptions());
    }

    #[Computed]
    public function report(): ReportSeriesData
    {
        try {
            return app(ReportService::class)->revenueReport($this->rangeFilters());
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;

            return ReportSeriesData::from($this->fallbackReport());
        }
    }

    #[Computed]
    public function revenue(): ProviderRevenueData
    {
        try {
            return app(ReportService::class)->revenue($this->rangeFilters());
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;

            return ProviderRevenueData::from($this->fallbackRevenue());
        }
    }

    public function usingFallback(): bool
    {
        $this->report;
        $this->revenue;

        return $this->fallbackUsed;
    }

    /** @return array{revenue:int, bookings:int, clients:int, growth:float} */
    #[Computed]
    public function kpis(): array
    {
        $s = $this->report->summary;

        return [
            'revenue' => (int) ($s['revenue'] ?? 0),
            'bookings' => (int) ($s['bookings'] ?? 0),
            'clients' => (int) ($s['active_clients'] ?? 0),
            'growth' => (float) ($s['growth'] ?? 0),
        ];
    }

    /** @return array{from_date:string, to_date:string, group_by:string} */
    private function rangeFilters(): array
    {
        [$days, $group] = match ($this->range) {
            '30d' => [30, 'day'],
            '6m' => [180, 'month'],
            default => [90, 'week'],
        };

        return [
            'from_date' => Carbon::today()->subDays($days - 1)->toDateString(),
            'to_date' => Carbon::today()->toDateString(),
            'group_by' => $group,
        ];
    }

    /** Revenue vs Expenses area chart. @return array<string, mixed> */
    public function revenueLineOptions(): array
    {
        $r = $this->report;
        $series = array_map(function ($ds) {
            return [
                'name' => (string) ($ds['label'] ?? ''),
                'data' => array_map(fn ($v) => round(((int) $v) / 100, 2), (array) ($ds['data'] ?? [])),
            ];
        }, $r->datasets);

        return [
            'chart' => ['type' => 'area', 'height' => 300, 'fontFamily' => 'inherit', 'foreColor' => '#94a3b8', 'toolbar' => ['show' => false], 'zoom' => ['enabled' => false]],
            'series' => array_values($series),
            'xaxis' => ['categories' => $r->labels, 'labels' => ['rotate' => -45, 'hideOverlappingLabels' => true]],
            'colors' => ['#00b166', '#ef4444'],
            'fill' => ['type' => 'gradient', 'gradient' => ['shadeIntensity' => 1, 'opacityFrom' => 0.3, 'opacityTo' => 0.02]],
            'stroke' => ['curve' => 'smooth', 'width' => 2.5],
            'dataLabels' => ['enabled' => false],
            'legend' => ['position' => 'top'],
            'grid' => ['borderColor' => 'rgba(148,163,184,0.15)', 'strokeDashArray' => 4],
        ];
    }

    /** Revenue by branch bar. @return array<string, mixed> */
    public function branchBarOptions(): array
    {
        $rows = $this->revenue->by_branch;

        return $this->barOptions(
            array_map(fn ($b) => (string) ($b['branch_name'] ?? '—'), $rows),
            array_map(fn ($b) => round(((int) ($b['revenue'] ?? 0)) / 100, 2), $rows),
            '#00b166',
        );
    }

    /** Revenue by employee bar. @return array<string, mixed> */
    public function employeeBarOptions(): array
    {
        $rows = $this->revenue->by_employee;

        return $this->barOptions(
            array_map(fn ($e) => (string) ($e['employee_name'] ?? '—'), $rows),
            array_map(fn ($e) => round(((int) ($e['revenue'] ?? 0)) / 100, 2), $rows),
            '#3b82f6',
        );
    }

    /**
     * @param  array<int, string>  $categories
     * @param  array<int, float>  $data
     * @return array<string, mixed>
     */
    private function barOptions(array $categories, array $data, string $color): array
    {
        return [
            'chart' => ['type' => 'bar', 'height' => 280, 'fontFamily' => 'inherit', 'foreColor' => '#94a3b8', 'toolbar' => ['show' => false]],
            'series' => [['name' => 'Revenue', 'data' => $data]],
            'xaxis' => ['categories' => $categories],
            'colors' => [$color],
            'plotOptions' => ['bar' => ['horizontal' => true, 'borderRadius' => 4, 'barHeight' => '55%']],
            'dataLabels' => ['enabled' => false],
            'grid' => ['borderColor' => 'rgba(148,163,184,0.15)', 'strokeDashArray' => 4],
            'noData' => ['text' => 'No data'],
        ];
    }

    public function render()
    {
        return view('livewire.reports.overview');
    }

    /** @return array<string, mixed> */
    private function fallbackReport(): array
    {
        $labels = [];
        $revenue = [];
        $expenses = [];
        for ($i = 11; $i >= 0; $i--) {
            $labels[] = Carbon::today()->subWeeks($i)->isoFormat('MMM D');
            $revenue[] = 380000 + (int) (220000 * (0.5 + 0.5 * sin($i / 2.1))) + ($i % 4) * 30000;
            $expenses[] = 140000 + (int) (60000 * (0.5 + 0.5 * cos($i / 2.6)));
        }

        return [
            'labels' => $labels,
            'datasets' => [
                ['label' => 'الإيرادات', 'data' => $revenue],
                ['label' => 'المصروفات', 'data' => $expenses],
            ],
            'summary' => [
                'revenue' => array_sum($revenue),
                'bookings' => 486,
                'active_clients' => 214,
                'growth' => 12.4,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function fallbackRevenue(): array
    {
        return [
            'total_revenue' => 5480000,
            'by_branch' => [
                ['branch_name' => 'وسط البلد', 'revenue' => 3280000],
                ['branch_name' => 'القاهرة الجديدة', 'revenue' => 2200000],
            ],
            'by_employee' => [
                ['employee_name' => 'خالد حسن', 'revenue' => 1180000],
                ['employee_name' => 'ياسمين فاروق', 'revenue' => 970000],
                ['employee_name' => 'د. سارة أحمد', 'revenue' => 820000],
                ['employee_name' => 'منى عادل', 'revenue' => 640000],
            ],
        ];
    }
}
