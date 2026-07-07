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
#[Title('Time Tracking — Waqty')]
class TimeTracking extends Component
{
    use HandlesWaqtyErrors;

    /** A standard working day in minutes; anything beyond counts as overtime. */
    private const STANDARD_MINUTES = 480;

    public string $employeeFilter = 'all';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $currentPage = 1;

    public int $perPage = 10;

    // Manual entry / adjust slide-over
    public bool $showForm = false;

    public ?string $editingUuid = null;

    public string $form_employee = '';

    public string $form_date = '';

    public string $form_clock_in = '';

    public string $form_clock_out = '';

    /** @var array<int, array<string, mixed>>|null per-request memo */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    public function updatedEmployeeFilter(): void
    {
        $this->currentPage = 1;
    }

    public function updatedDateFrom(): void
    {
        $this->currentPage = 1;
    }

    public function updatedDateTo(): void
    {
        $this->currentPage = 1;
    }

    /** @return array<int, array<string, mixed>> */
    private function source(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $rows = app(EmployeeHrService::class)->timeTracking();
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $rows = $this->fallbackData();
        }

        $this->loaded = array_values(array_map(
            fn ($r) => $this->normalize(is_array($r) ? $r : []),
            $rows,
        ));

        return $this->loaded;
    }

    public function usingFallback(): bool
    {
        $this->source();

        return $this->fallbackUsed;
    }

    /** @return array<int, array<string, mixed>> */
    #[Computed]
    public function filtered(): array
    {
        $employee = $this->employeeFilter;
        $from = $this->dateFrom;
        $to = $this->dateTo;

        return array_values(array_filter($this->source(), function (array $r) use ($employee, $from, $to) {
            $matchesEmployee = $employee === 'all' || $r['employee'] === $employee;
            $matchesFrom = $from === '' || $r['date'] >= $from;
            $matchesTo = $to === '' || $r['date'] <= $to;

            return $matchesEmployee && $matchesFrom && $matchesTo;
        }));
    }

    /** @return array<int, array<string, mixed>> */
    #[Computed]
    public function paginated(): array
    {
        return array_slice($this->filtered(), ($this->currentPage - 1) * $this->perPage, $this->perPage);
    }

    #[Computed]
    public function total(): int
    {
        return count($this->filtered());
    }

    /** @return array{totalHours:string, overtime:string, avgPerDay:string} */
    #[Computed]
    public function kpis(): array
    {
        $rows = $this->source();
        $worked = array_sum(array_map(fn ($r) => (int) $r['worked_minutes'], $rows));
        $overtime = array_sum(array_map(fn ($r) => (int) $r['overtime_minutes'], $rows));
        $days = count(array_unique(array_map(fn ($r) => $r['date'], $rows)));

        return [
            'totalHours' => $this->hm($worked),
            'overtime' => $this->hm($overtime),
            'avgPerDay' => $this->hm($days > 0 ? intdiv($worked, $days) : 0),
        ];
    }

    /** @return array<string, string> value => label for the employee filter */
    #[Computed]
    public function employeeOptions(): array
    {
        $options = ['all' => __('emp.timeTracking.allEmployees')];
        foreach ($this->source() as $r) {
            if ($r['employee'] !== '') {
                $options[$r['employee']] = $r['employee'];
            }
        }

        return $options;
    }

    public function openCreate(): void
    {
        $this->reset(['editingUuid', 'form_employee', 'form_clock_in', 'form_clock_out']);
        $this->form_date = now()->format('Y-m-d');
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $uuid): void
    {
        $entry = collect($this->source())->firstWhere('uuid', $uuid);
        if (! $entry) {
            return;
        }
        $this->editingUuid = $uuid;
        $this->form_employee = (string) $entry['employee'];
        $this->form_date = (string) $entry['date'];
        $this->form_clock_in = (string) $entry['clock_in'];
        $this->form_clock_out = (string) $entry['clock_out'];
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_employee' => ['required', 'string', 'max:100'],
            'form_date' => ['required', 'date'],
            'form_clock_in' => ['required', 'date_format:H:i'],
            'form_clock_out' => ['required', 'date_format:H:i'],
        ]);

        $worked = $this->minutesBetween($this->form_clock_in, $this->form_clock_out);
        if ($worked <= 0) {
            $this->addError('form_clock_out', __('emp.timeTracking.clockOutError'));

            return;
        }

        $payload = [
            'employee' => trim($this->form_employee),
            'date' => $this->form_date,
            'clock_in' => $this->form_clock_in,
            'clock_out' => $this->form_clock_out,
            'worked_minutes' => $worked,
            'overtime_minutes' => max(0, $worked - self::STANDARD_MINUTES),
        ];

        $result = $this->waqty(function () use ($payload) {
            $service = app(EmployeeHrService::class);
            $this->editingUuid
                ? $service->updateTimeEntry($this->editingUuid, $payload)
                : $service->createTimeEntry($payload);

            return true;
        }, __('emp.timeTracking.saveFailed'));

        if ($result || $this->usingFallback()) {
            $this->showForm = false;
            $this->editingUuid = null;
            $this->loaded = null;
            unset($this->filtered, $this->paginated, $this->total, $this->kpis, $this->employeeOptions);
            $this->dispatch('notify', type: 'success', message: __('emp.timeTracking.saved'));
        }
    }

    public function export(): void
    {
        $this->dispatch('notify', type: 'success', message: __('emp.timeTracking.exported'));
    }

    public function render()
    {
        return view('livewire.employees.time-tracking');
    }

    /** Format a minute count as "h:mm" (e.g. 510 -> "8:30"). */
    private function hm(int $minutes): string
    {
        $minutes = max(0, $minutes);

        return sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    /** Minutes elapsed between two "H:i" clock strings (0 when malformed or non-positive). */
    private function minutesBetween(string $in, string $out): int
    {
        if (! str_contains($in, ':') || ! str_contains($out, ':')) {
            return 0;
        }
        [$ih, $im] = array_map('intval', explode(':', $in, 2));
        [$oh, $om] = array_map('intval', explode(':', $out, 2));

        return ($oh * 60 + $om) - ($ih * 60 + $im);
    }

    /**
     * Shape a raw API/sample row into the columns this screen renders,
     * deriving worked and overtime minutes when the API omits them.
     *
     * @param  array<string, mixed>  $r
     * @return array<string, mixed>
     */
    private function normalize(array $r): array
    {
        $in = (string) ($r['clock_in'] ?? '');
        $out = (string) ($r['clock_out'] ?? '');
        $worked = isset($r['worked_minutes'])
            ? (int) $r['worked_minutes']
            : ($in !== '' && $out !== '' ? max(0, $this->minutesBetween($in, $out)) : 0);
        $overtime = isset($r['overtime_minutes'])
            ? (int) $r['overtime_minutes']
            : max(0, $worked - self::STANDARD_MINUTES);

        return [
            'uuid' => (string) ($r['uuid'] ?? $r['id'] ?? ''),
            'employee' => (string) ($r['employee'] ?? $r['employee_name'] ?? ''),
            'date' => (string) ($r['date'] ?? ''),
            'clock_in' => $in,
            'clock_out' => $out,
            'worked_minutes' => $worked,
            'overtime_minutes' => $overtime,
            'status' => $out !== '' ? 'checked_out' : 'checked_in',
        ];
    }

    /**
     * Arabic sample time records for graceful degradation when the API is down.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'TT1', 'employee' => 'سارة أحمد', 'date' => '2026-07-06', 'clock_in' => '09:00', 'clock_out' => '17:30', 'worked_minutes' => 510, 'overtime_minutes' => 30, 'status' => 'checked_out'],
            ['uuid' => 'TT2', 'employee' => 'منى عادل', 'date' => '2026-07-06', 'clock_in' => '08:45', 'clock_out' => '16:50', 'worked_minutes' => 485, 'overtime_minutes' => 5, 'status' => 'checked_out'],
            ['uuid' => 'TT3', 'employee' => 'خالد حسن', 'date' => '2026-07-06', 'clock_in' => '10:00', 'clock_out' => '19:15', 'worked_minutes' => 555, 'overtime_minutes' => 75, 'status' => 'checked_out'],
            ['uuid' => 'TT4', 'employee' => 'ياسمين فاروق', 'date' => '2026-07-05', 'clock_in' => '09:10', 'clock_out' => '15:40', 'worked_minutes' => 390, 'overtime_minutes' => 0, 'status' => 'checked_out'],
            ['uuid' => 'TT5', 'employee' => 'عمر نبيل', 'date' => '2026-07-05', 'clock_in' => '09:00', 'clock_out' => '17:00', 'worked_minutes' => 480, 'overtime_minutes' => 0, 'status' => 'checked_out'],
            ['uuid' => 'TT6', 'employee' => 'طارق سامي', 'date' => '2026-07-07', 'clock_in' => '09:05', 'clock_out' => null, 'worked_minutes' => 0, 'overtime_minutes' => 0, 'status' => 'checked_in'],
        ];
    }
}
