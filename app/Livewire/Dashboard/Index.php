<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Data\Waqty\DashboardData;
use App\Data\Waqty\DashboardSummaryData;
use App\Enums\BookingStatus;
use App\Services\Waqty\DashboardService;
use App\Services\Waqty\WaqtyApiException;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Dashboard — Waqty')]
class Index extends Component
{
    /** 7d | 30d | 90d */
    public string $range = '30d';

    private bool $fallbackUsed = false;

    public function updatedRange(): void
    {
        unset($this->summary, $this->counts, $this->nextUpcoming);
        $this->fallbackUsed = false;

        // Push fresh options to the (wire:ignore) charts without re-morphing them.
        $this->dispatch('dash-charts', revenue: $this->revenueOptions(), donut: $this->donutOptions());
    }

    #[Computed]
    public function summary(): DashboardSummaryData
    {
        try {
            return app(DashboardService::class)->summary($this->rangeFilters());
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;

            return DashboardSummaryData::from($this->fallbackSummary());
        }
    }

    #[Computed]
    public function counts(): DashboardData
    {
        try {
            return app(DashboardService::class)->dashboard();
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;

            return DashboardData::from($this->fallbackCounts());
        }
    }

    /** @return array<string, mixed>|null */
    #[Computed]
    public function nextUpcoming(): ?array
    {
        try {
            return app(DashboardService::class)->nextUpcoming();
        } catch (WaqtyApiException) {
            return $this->fallbackUsed ? $this->fallbackNext() : null;
        }
    }

    public function usingFallback(): bool
    {
        // Touch both computed sources so the flag reflects either failing.
        $this->summary;
        $this->counts;

        return $this->fallbackUsed;
    }

    /** @return array{from_date:string, to_date:string, group_by:string} */
    private function rangeFilters(): array
    {
        $days = match ($this->range) {
            '7d' => 7,
            '90d' => 90,
            default => 30,
        };

        return [
            'from_date' => Carbon::today()->subDays($days - 1)->toDateString(),
            'to_date' => Carbon::today()->toDateString(),
            'group_by' => 'day',
        ];
    }

    /** ApexCharts options for the booking-status donut. @return array<string, mixed> */
    public function donutOptions(): array
    {
        $dist = $this->summary->booking_status_distribution;
        $labels = array_map(function ($r) {
            $status = (string) data_get($r, 'status', '');

            return BookingStatus::tryFrom($status)?->label()
                ?? ucwords(str_replace('_', ' ', $status));
        }, $dist);
        $series = array_map(fn ($r) => (int) data_get($r, 'count', 0), $dist);

        return [
            'chart' => ['type' => 'donut', 'height' => 280, 'fontFamily' => 'inherit', 'foreColor' => '#94a3b8'],
            'labels' => $labels,
            'series' => $series,
            'colors' => ['#f59e0b', '#3b82f6', '#8b5cf6', '#06b6d4', '#10b981', '#ef4444', '#6b7280'],
            'legend' => ['position' => 'bottom'],
            'stroke' => ['width' => 0],
            'dataLabels' => ['enabled' => false],
            'plotOptions' => ['pie' => ['donut' => ['size' => '68%']]],
            'noData' => ['text' => 'No data'],
        ];
    }

    /** ApexCharts options for the revenue-by-day area chart. @return array<string, mixed> */
    public function revenueOptions(): array
    {
        $rows = $this->summary->revenue_by_day;
        $categories = array_map(fn ($r) => (string) data_get($r, 'date', ''), $rows);
        // Series in major EGP units for readable axis values.
        $series = array_map(fn ($r) => round(((int) data_get($r, 'revenue', 0)) / 100, 2), $rows);

        return [
            'chart' => ['type' => 'area', 'height' => 280, 'fontFamily' => 'inherit', 'foreColor' => '#94a3b8', 'toolbar' => ['show' => false], 'zoom' => ['enabled' => false]],
            'series' => [['name' => 'Revenue', 'data' => $series]],
            'xaxis' => ['categories' => $categories, 'labels' => ['rotate' => -45, 'hideOverlappingLabels' => true], 'tickAmount' => 8],
            'colors' => ['#00b166'],
            'fill' => ['type' => 'gradient', 'gradient' => ['shadeIntensity' => 1, 'opacityFrom' => 0.35, 'opacityTo' => 0.02]],
            'stroke' => ['curve' => 'smooth', 'width' => 2.5],
            'dataLabels' => ['enabled' => false],
            'grid' => ['borderColor' => 'rgba(148,163,184,0.15)', 'strokeDashArray' => 4],
            'noData' => ['text' => 'No data'],
        ];
    }

    public function render()
    {
        return view('livewire.dashboard.index');
    }

    /** @return array<string, mixed> */
    private function fallbackSummary(): array
    {
        // Deterministic 30-day revenue curve so charts render without live data.
        $revenueByDay = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $wave = 120000 + (int) (60000 * (0.5 + 0.5 * sin($i / 3.2))) + ($i % 5) * 9000;
            $revenueByDay[] = ['date' => $date->format('M j'), 'revenue' => $wave];
        }

        return [
            'total_revenue' => array_sum(array_column($revenueByDay, 'revenue')),
            'total_bookings' => 486,
            'new_clients' => 63,
            'total_invoices' => 402,
            'total_returns' => 7,
            'revenue_trend' => 12.4,
            'bookings_trend' => 8.1,
            'clients_trend' => -3.2,
            'top_services' => [
                ['name' => 'صبغة شعر', 'revenue' => 1350000, 'count' => 90],
                ['name' => 'قصّة شعر كلاسيك', 'revenue' => 900000, 'count' => 120],
                ['name' => 'مكياج عرائس', 'revenue' => 750000, 'count' => 5],
                ['name' => 'مساج الأنسجة العميقة', 'revenue' => 605000, 'count' => 44],
                ['name' => 'مانيكير', 'revenue' => 420000, 'count' => 84],
            ],
            'top_employees' => [
                ['name' => 'خالد حسن', 'revenue' => 1180000, 'bookings' => 143],
                ['name' => 'ياسمين فاروق', 'revenue' => 970000, 'bookings' => 118],
                ['name' => 'منى عادل', 'revenue' => 640000, 'bookings' => 96],
            ],
            'top_clients' => [
                ['name' => 'مريم عادل', 'visits' => 41, 'spent' => 3120000],
                ['name' => 'هناء فتحي', 'visits' => 33, 'spent' => 2450000],
                ['name' => 'ليلى حسن', 'visits' => 24, 'spent' => 1850000],
            ],
            'booking_status_distribution' => [
                ['status' => 'pending', 'count' => 34],
                ['status' => 'confirmed', 'count' => 88],
                ['status' => 'arrived', 'count' => 21],
                ['status' => 'in_service', 'count' => 12],
                ['status' => 'completed', 'count' => 298],
                ['status' => 'cancelled', 'count' => 24],
                ['status' => 'no_show', 'count' => 9],
            ],
            'revenue_by_day' => $revenueByDay,
            'occupancy_rate' => 74.5,
        ];
    }

    /** @return array<string, mixed> */
    private function fallbackCounts(): array
    {
        return [
            'bookings' => ['total' => 486, 'today' => ['total' => 18]],
            'revenue' => ['total' => 5480000, 'today' => 214000],
            'employees' => ['total' => 9, 'active' => 7, 'blocked' => 1],
            'branches' => ['total' => 2, 'active' => 2],
            'ratings' => ['total' => 214, 'average' => 4.7],
            'payments' => ['total_collected' => 5266000],
        ];
    }

    /** @return array<string, mixed> */
    private function fallbackNext(): array
    {
        return [
            'user' => ['name' => 'نور الدين'],
            'service' => ['name' => 'قصّة شعر كلاسيك'],
            'employee' => ['name' => 'خالد حسن'],
            'booking_date' => Carbon::today()->toDateString(),
            'start_time' => '14:30',
            'price' => 15000,
        ];
    }
}
