<?php

declare(strict_types=1);

namespace App\Livewire\Employees;

use App\Services\Waqty\EmployeeHrService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Attendance — Waqty')]
class Attendance extends Component
{
    use HandlesWaqtyErrors;

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $employeeFilter = 'all';

    public int $currentPage = 1;

    public int $perPage = 10;

    // Create / edit slide-over
    public bool $showForm = false;

    public ?string $editingUuid = null;

    public string $form_employee = '';

    public string $form_date = '';

    public string $form_check_in = '';

    public string $form_check_out = '';

    public string $form_status = 'present';

    // Delete confirmation
    public bool $showDelete = false;

    public ?string $deletingUuid = null;

    /** @var array<int, array<string, mixed>>|null per-request memo */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    public function updatedDateFrom(): void
    {
        $this->currentPage = 1;
    }

    public function updatedDateTo(): void
    {
        $this->currentPage = 1;
    }

    public function updatedEmployeeFilter(): void
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
            $rows = app(EmployeeHrService::class)->attendance(['per_page' => 100]);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $rows = $this->fallbackData();
        }

        $this->loaded = array_map(fn ($r) => $this->normalize($r), $rows);

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
        $from = $this->dateFrom;
        $to = $this->dateTo;
        $employee = $this->employeeFilter;

        return array_values(array_filter($this->source(), function (array $r) use ($from, $to, $employee) {
            $date = (string) $r['date'];

            return ($employee === 'all' || $r['employee'] === $employee)
                && ($from === '' || ($date !== '' && $date >= $from))
                && ($to === '' || ($date !== '' && $date <= $to));
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

    /** @return array{present:int, absent:int, late:int, onLeave:int} */
    #[Computed]
    public function kpis(): array
    {
        $all = $this->source();

        return [
            'present' => count(array_filter($all, fn (array $r) => $r['status'] === 'present')),
            'absent' => count(array_filter($all, fn (array $r) => $r['status'] === 'absent')),
            'late' => count(array_filter($all, fn (array $r) => $r['status'] === 'late')),
            'onLeave' => count(array_filter($all, fn (array $r) => $r['status'] === 'on_leave')),
        ];
    }

    /** Attendance status enum values (English); labels resolve via emp.attendance.status*. @return array<int, string> */
    public function statuses(): array
    {
        return ['present', 'absent', 'late', 'on_leave'];
    }

    /** Distinct employee names for the employee <select> filter and form. @return array<int, string> */
    #[Computed]
    public function employeeOptions(): array
    {
        return array_values(array_unique(array_filter(array_map(fn (array $r) => $r['employee'], $this->source()))));
    }

    public function openCreate(): void
    {
        $this->reset(['editingUuid', 'form_employee', 'form_check_in', 'form_check_out']);
        $this->form_date = Carbon::now()->format('Y-m-d');
        $this->form_status = 'present';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $uuid): void
    {
        $row = collect($this->source())->firstWhere('uuid', $uuid);
        if (! $row) {
            return;
        }

        $this->editingUuid = $uuid;
        $this->form_employee = (string) ($row['employee'] ?? '');
        $this->form_date = (string) ($row['date'] ?? '');
        $this->form_check_in = (string) ($row['check_in'] ?? '');
        $this->form_check_out = (string) ($row['check_out'] ?? '');
        $this->form_status = (string) ($row['status'] ?? 'present');
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_employee' => ['required', 'string', 'max:120'],
            'form_date' => ['required', 'date'],
            'form_status' => ['required', 'in:present,absent,late,on_leave'],
            'form_check_in' => ['nullable', 'date_format:H:i'],
            'form_check_out' => ['nullable', 'date_format:H:i'],
        ], [
            'form_employee.required' => __('emp.attendance.employeeRequired'),
            'form_date.required' => __('emp.attendance.dateRequired'),
            'form_status.required' => __('emp.attendance.statusRequired'),
            'form_check_in.date_format' => __('emp.attendance.timeFormat'),
            'form_check_out.date_format' => __('emp.attendance.timeFormat'),
        ]);

        $payload = [
            'employee' => trim($this->form_employee),
            'date' => $this->form_date,
            'check_in' => $this->form_check_in ?: null,
            'check_out' => $this->form_check_out ?: null,
            'status' => $this->form_status,
        ];

        $result = $this->waqty(function () use ($payload) {
            $service = app(EmployeeHrService::class);
            $this->editingUuid
                ? $service->updateAttendance($this->editingUuid, $payload)
                : $service->addManualAttendance($payload);

            return true;
        }, __('waqty.genericError'));

        if ($result || $this->usingFallback()) {
            $this->showForm = false;
            $this->editingUuid = null;
            $this->loaded = null;
            unset($this->filtered, $this->paginated, $this->total, $this->kpis, $this->employeeOptions);
            $this->dispatch('notify', type: 'success', message: __('common.saved'));
        }
    }

    public function confirmDelete(string $uuid): void
    {
        $this->deletingUuid = $uuid;
        $this->showDelete = true;
    }

    public function deleteAttendance(): void
    {
        if (! $this->deletingUuid) {
            return;
        }

        $uuid = $this->deletingUuid;
        $result = $this->waqty(fn () => app(EmployeeHrService::class)->deleteAttendance($uuid) ?? true, __('waqty.genericError'));

        $this->showDelete = false;
        $this->deletingUuid = null;

        if ($result || $this->usingFallback()) {
            $this->loaded = null;
            unset($this->filtered, $this->paginated, $this->total, $this->kpis, $this->employeeOptions);
            $this->dispatch('notify', type: 'success', message: __('common.deleted'));
        }
    }

    public function exportAttendance(): void
    {
        $this->dispatch('notify', type: 'success', message: __('emp.attendance.exportStarted'));
    }

    public function render()
    {
        return view('livewire.employees.attendance');
    }

    /**
     * Shape a raw API/sample row into the fields this screen renders,
     * deriving worked hours from the check-in/check-out marks.
     *
     * @param  array<string, mixed>  $r
     * @return array<string, mixed>
     */
    private function normalize(array $r): array
    {
        $status = (string) ($r['status'] ?? 'present');
        $checkIn = (string) ($r['check_in'] ?? $r['checkIn'] ?? '');
        $checkOut = (string) ($r['check_out'] ?? $r['checkOut'] ?? '');

        return [
            'uuid' => (string) ($r['uuid'] ?? $r['id'] ?? ''),
            'employee' => (string) ($r['employee'] ?? $r['employee_name'] ?? $r['name'] ?? ''),
            'date' => (string) ($r['date'] ?? ''),
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'status' => in_array($status, ['present', 'absent', 'late', 'on_leave'], true) ? $status : 'present',
            'hours' => ($checkIn !== '' && $checkOut !== '') ? $this->workedHours($checkIn, $checkOut) : null,
        ];
    }

    /** Duration in hours between two HH:MM marks, wrapping past midnight. */
    private function workedHours(string $from, string $to): float
    {
        [$fh, $fm] = array_pad(explode(':', $from), 2, '0');
        [$th, $tm] = array_pad(explode(':', $to), 2, '0');
        $start = (int) $fh * 60 + (int) $fm;
        $end = (int) $th * 60 + (int) $tm;
        if ($end <= $start) {
            $end += 24 * 60;
        }

        return ($end - $start) / 60;
    }

    /**
     * Local Arabic sample attendance for graceful degradation. Status enum
     * values (present|absent|late|on_leave) stay English; times are HH:MM.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'AT1', 'employee' => 'سارة أحمد', 'date' => '2026-07-07', 'check_in' => '08:58', 'check_out' => '17:05', 'status' => 'present'],
            ['uuid' => 'AT2', 'employee' => 'منى عادل', 'date' => '2026-07-07', 'check_in' => '09:00', 'check_out' => '16:30', 'status' => 'present'],
            ['uuid' => 'AT3', 'employee' => 'خالد حسن', 'date' => '2026-07-07', 'check_in' => '09:35', 'check_out' => '17:20', 'status' => 'late'],
            ['uuid' => 'AT4', 'employee' => 'ياسمين فاروق', 'date' => '2026-07-07', 'check_in' => '', 'check_out' => '', 'status' => 'absent'],
            ['uuid' => 'AT5', 'employee' => 'طارق سامي', 'date' => '2026-07-06', 'check_in' => '', 'check_out' => '', 'status' => 'on_leave'],
            ['uuid' => 'AT6', 'employee' => 'عمر نبيل', 'date' => '2026-07-06', 'check_in' => '12:10', 'check_out' => '20:15', 'status' => 'present'],
        ];
    }
}
