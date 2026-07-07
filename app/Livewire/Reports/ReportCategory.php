<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Data\Waqty\ReportSeriesData;
use App\Services\Waqty\ReportService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Report category HUB (/reports/{category}). Shows a KPI row + one chart built
 * from ReportService->report($category, filters), plus a grid of links to the
 * category's drill-down sub-reports (/reports/{category}/{report}).
 */
#[Layout('components.layouts.app')]
#[Title('Reports — Waqty')]
class ReportCategory extends Component
{
    public const CATEGORIES = ['revenue', 'bookings', 'clients', 'employees', 'services', 'custom'];

    public string $category = 'revenue';

    /** 1m | 3m | 6m */
    public string $range = '3m';

    public string $branch = '';

    private bool $fallbackUsed = false;

    public function mount(string $category): void
    {
        abort_unless(in_array($category, self::CATEGORIES, true), 404);

        $this->category = $category;
    }

    public function updatedRange(): void
    {
        $this->refresh();
    }

    public function updatedBranch(): void
    {
        $this->refresh();
    }

    private function refresh(): void
    {
        unset($this->report, $this->kpis);
        $this->fallbackUsed = false;
        $this->dispatch('reports-charts', chart: $this->chartOptions());
    }

    #[Computed]
    public function report(): ReportSeriesData
    {
        try {
            return app(ReportService::class)->report($this->category, $this->rangeFilters());
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;

            return ReportSeriesData::from($this->fallbackReport());
        }
    }

    public function usingFallback(): bool
    {
        $this->report;

        return $this->fallbackUsed;
    }

    /** The definition (title, kpis, chart, sub-reports) for the active category. */
    #[Computed]
    public function def(): array
    {
        return $this->catalog()[$this->category];
    }

    /**
     * KPI cards derived from the report summary + the category KPI definitions.
     *
     * @return array<int, array{label:string, value:string, icon:string, iconClass:string}>
     */
    #[Computed]
    public function kpis(): array
    {
        $summary = $this->report->summary;

        return array_map(function (array $kpi) use ($summary) {
            return [
                'label' => __($kpi['label']),
                'value' => $this->formatKpi($kpi['format'], $summary[$kpi['field']] ?? 0),
                'icon' => $kpi['icon'],
                'iconClass' => $kpi['iconClass'],
            ];
        }, $this->def()['kpis']);
    }

    private function formatKpi(string $format, mixed $value): string
    {
        return match ($format) {
            'money' => Money::compact((int) $value),
            'percent' => number_format((float) $value, 0).'%',
            'rating' => number_format((float) $value, 1),
            'text' => (string) $value,
            default => number_format((int) $value),
        };
    }

    /** ApexCharts options for the single category chart. @return array<string, mixed> */
    public function chartOptions(): array
    {
        $chart = $this->def()['chart'];
        $report = $this->report;

        $series = array_map(fn (array $ds) => [
            'name' => (string) ($ds['label'] ?? ''),
            'data' => array_map(
                fn ($v) => $chart['money'] ? round(((int) $v) / 100, 2) : (int) $v,
                (array) ($ds['data'] ?? []),
            ),
        ], $report->datasets);

        $base = [
            'series' => array_values($series),
            'colors' => $chart['colors'],
            'dataLabels' => ['enabled' => false],
            'grid' => ['borderColor' => 'rgba(148,163,184,0.15)', 'strokeDashArray' => 4],
            'noData' => ['text' => __('common.noData')],
        ];

        if ($chart['type'] === 'line') {
            return array_merge($base, [
                'chart' => ['type' => 'area', 'height' => 320, 'fontFamily' => 'inherit', 'foreColor' => '#94a3b8', 'toolbar' => ['show' => false], 'zoom' => ['enabled' => false]],
                'xaxis' => ['categories' => $report->labels, 'labels' => ['rotate' => -45, 'hideOverlappingLabels' => true]],
                'fill' => ['type' => 'gradient', 'gradient' => ['shadeIntensity' => 1, 'opacityFrom' => 0.3, 'opacityTo' => 0.02]],
                'stroke' => ['curve' => 'smooth', 'width' => 2.5],
                'legend' => ['position' => 'top'],
            ]);
        }

        return array_merge($base, [
            'chart' => ['type' => 'bar', 'height' => 320, 'fontFamily' => 'inherit', 'foreColor' => '#94a3b8', 'toolbar' => ['show' => false]],
            'xaxis' => ['categories' => $report->labels],
            'plotOptions' => ['bar' => array_merge(
                ['horizontal' => $chart['horizontal'], 'borderRadius' => 4],
                $chart['horizontal'] ? ['barHeight' => '55%'] : ['columnWidth' => '55%'],
            )],
        ]);
    }

    /** @return array{from_date:string, to_date:string, group_by:string, branch_uuid:string} */
    private function rangeFilters(): array
    {
        [$days, $group] = match ($this->range) {
            '1m' => [30, 'day'],
            '6m' => [180, 'month'],
            default => [90, 'week'],
        };

        return [
            'from_date' => Carbon::today()->subDays($days - 1)->toDateString(),
            'to_date' => Carbon::today()->toDateString(),
            'group_by' => $group,
            'branch_uuid' => $this->branch,
        ];
    }

    public function render()
    {
        return view('livewire.reports.category');
    }

    /**
     * Per-category hub definition: headline title, KPI fields, chart config and
     * the drill-down sub-reports. All copy is i18n keys under reports.*.
     *
     * @return array<string, array<string, mixed>>
     */
    private function catalog(): array
    {
        return [
            'revenue' => [
                'title' => 'reports.cat.revenue.title',
                'chart' => ['title' => 'reports.chart.monthlyRevenueTrend', 'type' => 'line', 'money' => true, 'horizontal' => false, 'colors' => ['#00b166']],
                'kpis' => [
                    ['label' => 'reports.kpi.totalRevenue', 'field' => 'total_revenue', 'format' => 'money', 'icon' => 'wallet', 'iconClass' => 'bg-primary-100 text-primary-600'],
                    ['label' => 'reports.kpi.avgTransaction', 'field' => 'avg_transaction', 'format' => 'money', 'icon' => 'receipt', 'iconClass' => 'bg-info-light text-info'],
                    ['label' => 'reports.kpi.refunds', 'field' => 'refunds', 'format' => 'money', 'icon' => 'rotate-ccw', 'iconClass' => 'bg-warning-light text-warning'],
                    ['label' => 'reports.kpi.netProfit', 'field' => 'net_profit', 'format' => 'money', 'icon' => 'trending-up', 'iconClass' => 'bg-success-light text-success'],
                ],
                'reports' => [
                    ['slug' => 'daily-revenue', 'title' => 'reports.rpt.dailyRevenue.title', 'desc' => 'reports.rpt.dailyRevenue.desc', 'icon' => 'calendar-days'],
                    ['slug' => 'payment-methods', 'title' => 'reports.rpt.paymentMethods.title', 'desc' => 'reports.rpt.paymentMethods.desc', 'icon' => 'wallet'],
                    ['slug' => 'service-revenue', 'title' => 'reports.rpt.serviceRevenue.title', 'desc' => 'reports.rpt.serviceRevenue.desc', 'icon' => 'scissors'],
                    ['slug' => 'tax-report', 'title' => 'reports.rpt.taxReport.title', 'desc' => 'reports.rpt.taxReport.desc', 'icon' => 'receipt'],
                ],
            ],
            'bookings' => [
                'title' => 'reports.cat.bookings.title',
                'chart' => ['title' => 'reports.chart.weeklyBookingVolume', 'type' => 'bar', 'money' => false, 'horizontal' => false, 'colors' => ['#3b82f6']],
                'kpis' => [
                    ['label' => 'reports.kpi.totalBookings', 'field' => 'total_bookings', 'format' => 'number', 'icon' => 'calendar-check', 'iconClass' => 'bg-primary-100 text-primary-600'],
                    ['label' => 'reports.kpi.completed', 'field' => 'completed', 'format' => 'number', 'icon' => 'check-circle-2', 'iconClass' => 'bg-success-light text-success'],
                    ['label' => 'reports.kpi.cancelled', 'field' => 'cancelled', 'format' => 'number', 'icon' => 'ban', 'iconClass' => 'bg-error-light text-error'],
                    ['label' => 'reports.kpi.noShow', 'field' => 'no_show', 'format' => 'number', 'icon' => 'alert-triangle', 'iconClass' => 'bg-warning-light text-warning'],
                ],
                'reports' => [
                    ['slug' => 'booking-history', 'title' => 'reports.rpt.bookingHistory.title', 'desc' => 'reports.rpt.bookingHistory.desc', 'icon' => 'calendar-check'],
                    ['slug' => 'cancellations', 'title' => 'reports.rpt.cancellations.title', 'desc' => 'reports.rpt.cancellations.desc', 'icon' => 'ban'],
                    ['slug' => 'utilization', 'title' => 'reports.rpt.utilization.title', 'desc' => 'reports.rpt.utilization.desc', 'icon' => 'gauge'],
                    ['slug' => 'sources', 'title' => 'reports.rpt.sources.title', 'desc' => 'reports.rpt.sources.desc', 'icon' => 'globe'],
                ],
            ],
            'clients' => [
                'title' => 'reports.cat.clients.title',
                'chart' => ['title' => 'reports.chart.newVsReturning', 'type' => 'line', 'money' => false, 'horizontal' => false, 'colors' => ['#00b166', '#3b82f6']],
                'kpis' => [
                    ['label' => 'reports.kpi.totalClients', 'field' => 'total_clients', 'format' => 'number', 'icon' => 'users', 'iconClass' => 'bg-primary-100 text-primary-600'],
                    ['label' => 'reports.kpi.newClients', 'field' => 'new_clients', 'format' => 'number', 'icon' => 'user-plus', 'iconClass' => 'bg-success-light text-success'],
                    ['label' => 'reports.kpi.returning', 'field' => 'returning', 'format' => 'number', 'icon' => 'rotate-ccw', 'iconClass' => 'bg-info-light text-info'],
                    ['label' => 'reports.kpi.lostClients', 'field' => 'lost_clients', 'format' => 'number', 'icon' => 'trending-down', 'iconClass' => 'bg-error-light text-error'],
                ],
                'reports' => [
                    ['slug' => 'top-spenders', 'title' => 'reports.rpt.topSpenders.title', 'desc' => 'reports.rpt.topSpenders.desc', 'icon' => 'trending-up'],
                    ['slug' => 'retention', 'title' => 'reports.rpt.retention.title', 'desc' => 'reports.rpt.retention.desc', 'icon' => 'rotate-ccw'],
                    ['slug' => 'feedback', 'title' => 'reports.rpt.feedback.title', 'desc' => 'reports.rpt.feedback.desc', 'icon' => 'star'],
                    ['slug' => 'demographics', 'title' => 'reports.rpt.demographics.title', 'desc' => 'reports.rpt.demographics.desc', 'icon' => 'users'],
                ],
            ],
            'employees' => [
                'title' => 'reports.cat.employees.title',
                'chart' => ['title' => 'reports.chart.revenuePerEmployee', 'type' => 'bar', 'money' => true, 'horizontal' => true, 'colors' => ['#00b166']],
                'kpis' => [
                    ['label' => 'reports.kpi.activeStaff', 'field' => 'active_staff', 'format' => 'number', 'icon' => 'user-cog', 'iconClass' => 'bg-primary-100 text-primary-600'],
                    ['label' => 'reports.kpi.avgRevenue', 'field' => 'avg_revenue', 'format' => 'money', 'icon' => 'wallet', 'iconClass' => 'bg-info-light text-info'],
                    ['label' => 'reports.kpi.avgRating', 'field' => 'avg_rating', 'format' => 'rating', 'icon' => 'star', 'iconClass' => 'bg-warning-light text-warning'],
                    ['label' => 'reports.kpi.utilization', 'field' => 'utilization', 'format' => 'percent', 'icon' => 'gauge', 'iconClass' => 'bg-success-light text-success'],
                ],
                'reports' => [
                    ['slug' => 'commissions', 'title' => 'reports.rpt.commissions.title', 'desc' => 'reports.rpt.commissions.desc', 'icon' => 'wallet'],
                    ['slug' => 'employee-sales', 'title' => 'reports.rpt.employeeSales.title', 'desc' => 'reports.rpt.employeeSales.desc', 'icon' => 'bar-chart-3'],
                    ['slug' => 'attendance', 'title' => 'reports.rpt.attendance.title', 'desc' => 'reports.rpt.attendance.desc', 'icon' => 'clock'],
                    ['slug' => 'quality', 'title' => 'reports.rpt.quality.title', 'desc' => 'reports.rpt.quality.desc', 'icon' => 'star'],
                ],
            ],
            'services' => [
                'title' => 'reports.cat.services.title',
                'chart' => ['title' => 'reports.chart.top6Services', 'type' => 'bar', 'money' => true, 'horizontal' => true, 'colors' => ['#8b5cf6']],
                'kpis' => [
                    ['label' => 'reports.kpi.activeServices', 'field' => 'active_services', 'format' => 'number', 'icon' => 'sparkles', 'iconClass' => 'bg-primary-100 text-primary-600'],
                    ['label' => 'reports.kpi.topService', 'field' => 'top_service', 'format' => 'text', 'icon' => 'star', 'iconClass' => 'bg-warning-light text-warning'],
                    ['label' => 'reports.kpi.avgRevenuePerService', 'field' => 'avg_revenue_per_service', 'format' => 'money', 'icon' => 'wallet', 'iconClass' => 'bg-info-light text-info'],
                    ['label' => 'reports.kpi.lowDemand', 'field' => 'low_demand', 'format' => 'number', 'icon' => 'trending-down', 'iconClass' => 'bg-error-light text-error'],
                ],
                'reports' => [
                    ['slug' => 'popularity', 'title' => 'reports.rpt.popularity.title', 'desc' => 'reports.rpt.popularity.desc', 'icon' => 'trending-up'],
                    ['slug' => 'revenue', 'title' => 'reports.rpt.serviceRevenueBreakdown.title', 'desc' => 'reports.rpt.serviceRevenueBreakdown.desc', 'icon' => 'wallet'],
                    ['slug' => 'duration', 'title' => 'reports.rpt.duration.title', 'desc' => 'reports.rpt.duration.desc', 'icon' => 'clock'],
                    ['slug' => 'categories', 'title' => 'reports.rpt.categories.title', 'desc' => 'reports.rpt.categories.desc', 'icon' => 'layout-dashboard'],
                ],
            ],
            'custom' => [
                'title' => 'reports.cat.custom.title',
                'chart' => ['title' => 'reports.chart.reportGenFrequency', 'type' => 'bar', 'money' => false, 'horizontal' => false, 'colors' => ['#f59e0b']],
                'kpis' => [
                    ['label' => 'reports.kpi.savedReports', 'field' => 'saved_reports', 'format' => 'number', 'icon' => 'bar-chart-3', 'iconClass' => 'bg-primary-100 text-primary-600'],
                    ['label' => 'reports.kpi.scheduled', 'field' => 'scheduled', 'format' => 'number', 'icon' => 'clock', 'iconClass' => 'bg-info-light text-info'],
                    ['label' => 'reports.kpi.lastGenerated', 'field' => 'last_generated', 'format' => 'text', 'icon' => 'calendar-check', 'iconClass' => 'bg-success-light text-success'],
                    ['label' => 'reports.kpi.dataSources', 'field' => 'data_sources', 'format' => 'number', 'icon' => 'activity', 'iconClass' => 'bg-purple-100 text-purple-600'],
                ],
                'reports' => [
                    ['slug' => 'revenue-bookings', 'title' => 'reports.rpt.revenueBookings.title', 'desc' => 'reports.rpt.revenueBookings.desc', 'icon' => 'bar-chart-3'],
                    ['slug' => 'employee-efficiency', 'title' => 'reports.rpt.employeeEfficiency.title', 'desc' => 'reports.rpt.employeeEfficiency.desc', 'icon' => 'gauge'],
                    ['slug' => 'client-ltv', 'title' => 'reports.rpt.clientLtv.title', 'desc' => 'reports.rpt.clientLtv.desc', 'icon' => 'trending-up'],
                    ['slug' => 'monthly-summary', 'title' => 'reports.rpt.monthlySummary.title', 'desc' => 'reports.rpt.monthlySummary.desc', 'icon' => 'receipt'],
                ],
            ],
        ];
    }

    /** Arabic sample series + summary, keyed by category. @return array<string, mixed> */
    private function fallbackReport(): array
    {
        return match ($this->category) {
            'bookings' => [
                'labels' => $this->weekLabels(),
                'datasets' => [['label' => 'الحجوزات', 'data' => [58, 72, 65, 84, 79, 91]]],
                'summary' => ['total_bookings' => 486, 'completed' => 402, 'cancelled' => 54, 'no_show' => 30],
            ],
            'clients' => [
                'labels' => $this->monthLabels(),
                'datasets' => [
                    ['label' => 'عملاء جدد', 'data' => [42, 55, 48, 63, 58, 71]],
                    ['label' => 'عائدون', 'data' => [88, 96, 91, 104, 99, 112]],
                ],
                'summary' => ['total_clients' => 1240, 'new_clients' => 337, 'returning' => 903, 'lost_clients' => 46],
            ],
            'employees' => [
                'labels' => ['خالد حسن', 'ياسمين فاروق', 'د. سارة أحمد', 'منى عادل', 'طارق سامي'],
                'datasets' => [['label' => 'الإيرادات', 'data' => [1180000, 970000, 820000, 640000, 410000]]],
                'summary' => ['active_staff' => 12, 'avg_revenue' => 804000, 'avg_rating' => 4.7, 'utilization' => 78],
            ],
            'services' => [
                'labels' => ['قص وتصفيف', 'صبغة شعر', 'عناية بالبشرة', 'مانيكير وباديكير', 'ليزر', 'مساج'],
                'datasets' => [['label' => 'الإيرادات', 'data' => [1450000, 1180000, 960000, 720000, 540000, 380000]]],
                'summary' => ['active_services' => 24, 'top_service' => 'قص وتصفيف', 'avg_revenue_per_service' => 213000, 'low_demand' => 3],
            ],
            'custom' => [
                'labels' => $this->monthLabels(),
                'datasets' => [['label' => 'التقارير', 'data' => [8, 12, 6, 15, 11, 18]]],
                'summary' => ['saved_reports' => 34, 'scheduled' => 6, 'last_generated' => __('reports.kpi.today'), 'data_sources' => 9],
            ],
            default => [
                'labels' => $this->monthLabels(),
                'datasets' => [['label' => 'الإيرادات', 'data' => [920000, 1080000, 990000, 1240000, 1150000, 1380000]]],
                'summary' => ['total_revenue' => 6760000, 'avg_transaction' => 42500, 'refunds' => 210000, 'net_profit' => 4180000],
            ],
        };
    }

    /** @return array<int, string> */
    private function monthLabels(): array
    {
        return array_map(fn (int $i) => Carbon::today()->subMonths($i)->isoFormat('MMM'), [5, 4, 3, 2, 1, 0]);
    }

    /** @return array<int, string> */
    private function weekLabels(): array
    {
        return array_map(fn (int $i) => Carbon::today()->subWeeks($i)->isoFormat('MMM D'), [5, 4, 3, 2, 1, 0]);
    }
}
