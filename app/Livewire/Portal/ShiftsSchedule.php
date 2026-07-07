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
 * Employee Portal › Shifts — the signed-in employee's upcoming schedule.
 * Read-only; employee token surface. Falls back to sample data offline.
 */
#[Layout('components.layouts.employee')]
#[Title('My Shifts — Waqty')]
class ShiftsSchedule extends Component
{
    public string $month = '';

    /** @var array<int, array<string, mixed>> */
    public array $shifts = [];

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
        try {
            $all = app(EmployeePortalService::class)->shifts();
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $all = $this->fallbackData();
        }

        // Keep only shifts within the active month.
        $prefix = $this->month;
        $this->shifts = array_values(array_filter($all, fn ($s) => str_starts_with((string) ($s['date'] ?? ''), $prefix)));
    }

    public function usingFallback(): bool
    {
        return $this->fallbackUsed;
    }

    public function render()
    {
        return view('livewire.portal.shifts-schedule');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        $start = now()->startOfMonth();
        $out = [];
        $shifts = [
            ['label' => 'صباحية', 'start' => '09:00', 'end' => '17:00', 'branch' => 'فرع وسط البلد'],
            ['label' => 'مسائية', 'start' => '13:00', 'end' => '21:00', 'branch' => 'مول العرب'],
        ];
        for ($i = 0; $i < 10; $i++) {
            $day = (clone $start)->addDays($i * 2 + 1);
            $s = $shifts[$i % 2];
            $out[] = [
                'date' => $day->toDateString(),
                'label' => $s['label'],
                'start_time' => $s['start'],
                'end_time' => $s['end'],
                'branch' => $s['branch'],
            ];
        }

        return $out;
    }
}
