<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Data\Waqty\ProviderRevenueData;
use App\Services\Waqty\ReportService;
use App\Services\Waqty\WaqtyApiException;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Revenue — Waqty')]
class Revenue extends Component
{
    /** 30d | 3m | 6m */
    public string $range = '3m';

    private bool $fallbackUsed = false;

    public function updatedRange(): void
    {
        unset($this->revenue);
        $this->fallbackUsed = false;
        $this->dispatch('revenue-charts', branch: $this->branchBarOptions(), employee: $this->employeeBarOptions());
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
        $this->revenue;

        return $this->fallbackUsed;
    }

    /** @return array{start_date:string, end_date:string} */
    private function rangeFilters(): array
    {
        $days = match ($this->range) {
            '30d' => 30,
            '6m' => 180,
            default => 90,
        };

        return [
            'start_date' => Carbon::today()->subDays($days - 1)->toDateString(),
            'end_date' => Carbon::today()->toDateString(),
        ];
    }

    /** @return array<string, mixed> */
    public function branchBarOptions(): array
    {
        $rows = $this->revenue->by_branch;

        return $this->barOptions(
            array_map(fn ($b) => (string) ($b['branch_name'] ?? '—'), $rows),
            array_map(fn ($b) => round(((int) ($b['revenue'] ?? 0)) / 100, 2), $rows),
            '#00b166',
        );
    }

    /** @return array<string, mixed> */
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
            'chart' => ['type' => 'bar', 'height' => 300, 'fontFamily' => 'inherit', 'foreColor' => '#94a3b8', 'toolbar' => ['show' => false]],
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
        return view('livewire.reports.revenue');
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
                ['employee_name' => 'طارق سامي', 'revenue' => 410000],
            ],
        ];
    }
}
