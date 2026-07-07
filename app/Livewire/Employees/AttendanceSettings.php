<?php

declare(strict_types=1);

namespace App\Livewire\Employees;

use App\Services\Waqty\EmployeeHrService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Employees › Attendance Settings — a single-card policy form (no list).
 * Loads the current policy via EmployeeHrService::attendanceSettings() on
 * mount and persists it via updateAttendanceSettings(). When the API is down
 * the form is seeded with local sample defaults and saving stays local so the
 * screen keeps working for a demo.
 */
#[Layout('components.layouts.app')]
#[Title('Attendance Settings — Waqty')]
class AttendanceSettings extends Component
{
    use HandlesWaqtyErrors;

    public string $form_shift_start = '09:00';

    public string $form_shift_end = '17:00';

    public string $form_late_threshold = '15';

    public string $form_early_leave_threshold = '15';

    public string $form_overtime_multiplier = '1.5';

    public string $form_grace_period = '10';

    public string $form_auto_absent_after = '120';

    public bool $fallbackUsed = false;

    public function mount(): void
    {
        $data = $this->fetch();

        $this->form_shift_start = (string) ($data['shift_start'] ?? '09:00');
        $this->form_shift_end = (string) ($data['shift_end'] ?? '17:00');
        $this->form_late_threshold = (string) ($data['late_threshold'] ?? 15);
        $this->form_early_leave_threshold = (string) ($data['early_leave_threshold'] ?? 15);
        $this->form_overtime_multiplier = (string) ($data['overtime_multiplier'] ?? 1.5);
        $this->form_grace_period = (string) ($data['grace_period'] ?? 10);
        $this->form_auto_absent_after = (string) ($data['auto_absent_after'] ?? 120);
    }

    public function usingFallback(): bool
    {
        return $this->fallbackUsed;
    }

    public function save(): void
    {
        $this->validate([
            'form_shift_start' => ['required', 'date_format:H:i'],
            'form_shift_end' => ['required', 'date_format:H:i'],
            'form_late_threshold' => ['required', 'integer', 'min:0', 'max:240'],
            'form_early_leave_threshold' => ['required', 'integer', 'min:0', 'max:240'],
            'form_overtime_multiplier' => ['required', 'numeric', 'min:1', 'max:5'],
            'form_grace_period' => ['required', 'integer', 'min:0', 'max:120'],
            'form_auto_absent_after' => ['required', 'integer', 'min:0', 'max:480'],
        ]);

        $payload = [
            'shift_start' => $this->form_shift_start,
            'shift_end' => $this->form_shift_end,
            'late_threshold' => (int) $this->form_late_threshold,
            'early_leave_threshold' => (int) $this->form_early_leave_threshold,
            'overtime_multiplier' => (float) $this->form_overtime_multiplier,
            'grace_period' => (int) $this->form_grace_period,
            'auto_absent_after' => (int) $this->form_auto_absent_after,
        ];

        if (! $this->usingFallback()) {
            $ok = $this->waqty(
                fn () => app(EmployeeHrService::class)->updateAttendanceSettings($payload) ?? true,
                __('waqty.genericError')
            );

            if (! $ok) {
                return;
            }
        }

        $this->dispatch('notify', type: 'success', message: __('emp.attendanceSettings.saved'));
    }

    public function render()
    {
        return view('livewire.employees.attendance-settings');
    }

    /** Load the saved policy, falling back to local sample defaults on API failure. @return array<string, mixed> */
    private function fetch(): array
    {
        try {
            $data = app(EmployeeHrService::class)->attendanceSettings();

            return $data !== [] ? $data : $this->fallbackData();
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;

            return $this->fallbackData();
        }
    }

    /** Local sample policy for graceful degradation (values are locale-neutral times/minutes). @return array<string, mixed> */
    private function fallbackData(): array
    {
        return [
            'shift_start' => '09:00',
            'shift_end' => '17:00',
            'late_threshold' => 15,
            'early_leave_threshold' => 15,
            'overtime_multiplier' => 1.5,
            'grace_period' => 10,
            'auto_absent_after' => 120,
        ];
    }
}
