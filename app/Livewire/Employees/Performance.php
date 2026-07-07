<?php

declare(strict_types=1);

namespace App\Livewire\Employees;

use App\Services\Waqty\EmployeeHrService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Performance — Waqty')]
class Performance extends Component
{
    use HandlesWaqtyErrors;

    public string $period = 'month'; // month | quarter | year

    /** @var array<int, array<string, mixed>>|null per-request memo */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    /**
     * Employee rows ranked by revenue descending; the rank is derived so the
     * list stays consistent whatever order the API returns.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function ranking(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $rows = app(EmployeeHrService::class)->performance(['period' => $this->period]);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $rows = $this->fallbackData();
        }

        $rows = array_map(fn ($r) => $this->normalize($r), $rows);
        usort($rows, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        $rank = 0;
        foreach ($rows as &$row) {
            $row['rank'] = ++$rank;
        }
        unset($row);

        return $this->loaded = $rows;
    }

    public function usingFallback(): bool
    {
        $this->ranking();

        return $this->fallbackUsed;
    }

    public function updatedPeriod(): void
    {
        $this->loaded = null;
        unset($this->ranking, $this->kpis);
    }

    /** @return array{topPerformer:string, avgRating:float, totalRevenue:int} */
    #[Computed]
    public function kpis(): array
    {
        $rows = $this->ranking();

        if ($rows === []) {
            return ['topPerformer' => '—', 'avgRating' => 0.0, 'totalRevenue' => 0];
        }

        $ratings = array_map(fn ($r) => (float) $r['rating'], $rows);

        return [
            'topPerformer' => (string) $rows[0]['employee'],
            'avgRating' => round(array_sum($ratings) / count($ratings), 1),
            'totalRevenue' => array_sum(array_map(fn ($r) => (int) $r['revenue'], $rows)),
        ];
    }

    public function render()
    {
        return view('livewire.employees.performance');
    }

    /**
     * Shape a raw API/sample row into the columns this screen renders.
     *
     * @param  array<string, mixed>  $r
     * @return array<string, mixed>
     */
    private function normalize(array $r): array
    {
        return [
            'uuid' => (string) ($r['uuid'] ?? $r['id'] ?? ''),
            'employee' => (string) ($r['employee'] ?? $r['employee_name'] ?? $r['name'] ?? ''),
            'bookings' => (int) ($r['bookings'] ?? $r['bookings_count'] ?? 0),
            'revenue' => (int) ($r['revenue'] ?? 0),
            'rating' => round((float) ($r['rating'] ?? 0), 1),
            'utilization' => (int) round((float) ($r['utilization'] ?? $r['utilization_rate'] ?? 0)),
        ];
    }

    /**
     * Local Arabic sample ranking for graceful degradation, pre-sorted desc.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'P1', 'employee' => 'سارة أحمد', 'bookings' => 142, 'revenue' => 8450000, 'rating' => 4.9, 'utilization' => 92],
            ['uuid' => 'P2', 'employee' => 'منى عادل', 'bookings' => 128, 'revenue' => 7200000, 'rating' => 4.8, 'utilization' => 88],
            ['uuid' => 'P3', 'employee' => 'ياسمين فاروق', 'bookings' => 110, 'revenue' => 6100000, 'rating' => 4.6, 'utilization' => 81],
            ['uuid' => 'P4', 'employee' => 'خالد حسن', 'bookings' => 96, 'revenue' => 5400000, 'rating' => 4.5, 'utilization' => 74],
            ['uuid' => 'P5', 'employee' => 'طارق سامي', 'bookings' => 78, 'revenue' => 3900000, 'rating' => 4.2, 'utilization' => 63],
            ['uuid' => 'P6', 'employee' => 'عمر نبيل', 'bookings' => 54, 'revenue' => 2700000, 'rating' => 3.9, 'utilization' => 51],
        ];
    }
}
