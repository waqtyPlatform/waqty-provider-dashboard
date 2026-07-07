<?php

declare(strict_types=1);

namespace App\Livewire\Portal;

use App\Services\Waqty\EmployeePortalService;
use App\Services\Waqty\WaqtyApiException;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Employee Portal › Attendance history — month-navigable records + stat strip.
 * Read-only; employee token surface. Falls back to sample data offline.
 */
#[Layout('components.layouts.employee')]
#[Title('My Attendance — Waqty')]
class AttendanceHistory extends Component
{
    /** Active month as YYYY-MM. */
    public string $month = '';

    /** @var array<int, array<string, mixed>> */
    public array $records = [];

    public bool $fallbackUsed = false;

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
        $this->load();
    }

    public function prevMonth(): void
    {
        $this->month = Carbon::createFromFormat('Y-m', $this->month)->subMonth()->format('Y-m');
        $this->load();
    }

    public function nextMonth(): void
    {
        $this->month = Carbon::createFromFormat('Y-m', $this->month)->addMonth()->format('Y-m');
        $this->load();
    }

    public function monthLabel(): string
    {
        return Carbon::createFromFormat('Y-m', $this->month)->locale(app()->getLocale())->isoFormat('MMMM YYYY');
    }

    private function load(): void
    {
        $start = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        try {
            $this->records = app(EmployeePortalService::class)->attendance($start->toDateString(), $end->toDateString());
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->records = $this->fallbackData($start);
        }
    }

    public function usingFallback(): bool
    {
        return $this->fallbackUsed;
    }

    /** @return array{present:int, late:int, absent:int, hours:float} */
    public function stats(): array
    {
        $present = 0;
        $late = 0;
        $absent = 0;
        $minutes = 0;
        foreach ($this->records as $r) {
            $status = $r['status'] ?? 'present';
            match ($status) {
                'late' => $late++,
                'absent' => $absent++,
                default => $present++,
            };
            $minutes += (int) ($r['worked_minutes'] ?? 0);
        }

        return ['present' => $present, 'late' => $late, 'absent' => $absent, 'hours' => round($minutes / 60, 1)];
    }

    public function render()
    {
        return view('livewire.portal.attendance-history');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(Carbon $start): array
    {
        $out = [];
        $statuses = ['present', 'present', 'late', 'present', 'present', 'absent'];
        for ($i = 0; $i < 6; $i++) {
            $day = (clone $start)->addDays($i * 2);
            $status = $statuses[$i];
            $out[] = [
                'date' => $day->toDateString(),
                'status' => $status,
                'check_in' => $status === 'absent' ? null : ($status === 'late' ? '09:41' : '08:57'),
                'check_out' => $status === 'absent' ? null : '17:05',
                'worked_minutes' => $status === 'absent' ? 0 : 486,
            ];
        }

        return $out;
    }
}
