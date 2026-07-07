<?php

declare(strict_types=1);

namespace App\Livewire\Employees;

use App\Services\Waqty\EmployeeHrService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Schedule — Waqty')]
class Schedule extends Component
{
    use HandlesWaqtyErrors;

    /** Grid columns, Sunday → Saturday. Day keys stay English (enum-like). */
    private const DAYS = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

    /** 0 = current week, -1 previous, +1 next. */
    public int $weekOffset = 0;

    public bool $showForm = false;

    public ?string $editingUuid = null;

    public string $form_employee = '';

    public string $form_day = '';

    public string $form_start = '';

    public string $form_end = '';

    /** Optimistically-added shifts, demo-friendly when the API is down. @var array<int, array<string, mixed>> */
    public array $addedShifts = [];

    /** Optimistic edits to sample shifts keyed by uuid. @var array<string, array<string, string>> */
    public array $editedShifts = [];

    /** @var array<int, array<string, mixed>>|null per-request memo of normalized base rows */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    public function prevWeek(): void
    {
        $this->weekOffset--;
    }

    public function nextWeek(): void
    {
        $this->weekOffset++;
    }

    public function thisWeek(): void
    {
        $this->weekOffset = 0;
    }

    /** @return array<int, array<string, mixed>> normalized base rows from the API or fallback */
    private function baseShifts(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $rows = app(EmployeeHrService::class)->shifts();
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $rows = $this->fallbackData();
        }

        return $this->loaded = array_values(array_filter(
            array_map(fn ($r) => $this->normalize($r), $rows),
            fn ($r) => $r['employee'] !== '' && $r['day'] !== '',
        ));
    }

    public function usingFallback(): bool
    {
        $this->baseShifts();

        return $this->fallbackUsed;
    }

    /**
     * All shifts to render: base rows with optimistic edits applied, plus
     * any optimistically-added rows (fallback demo mode).
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function shifts(): array
    {
        $rows = array_map(function (array $r) {
            if (isset($this->editedShifts[$r['uuid']])) {
                $r = array_merge($r, $this->editedShifts[$r['uuid']]);
            }

            return $r;
        }, $this->baseShifts());

        foreach ($this->addedShifts as $a) {
            $rows[] = $this->normalize($a);
        }

        return $rows;
    }

    /**
     * Employee rows, each with shifts bucketed per weekday for the grid.
     *
     * @return array<int, array{employee:string, days:array<string, array<int, array<string,mixed>>>}>
     */
    #[Computed]
    public function roster(): array
    {
        $rows = [];

        foreach ($this->shifts() as $s) {
            if ($s['employee'] === '' || ! in_array($s['day'], self::DAYS, true)) {
                continue;
            }
            $name = $s['employee'];
            if (! isset($rows[$name])) {
                $rows[$name] = ['employee' => $name, 'days' => array_fill_keys(self::DAYS, [])];
            }
            $rows[$name]['days'][$s['day']][] = $s;
        }

        return array_values($rows);
    }

    /** Distinct employee names for the form <select>. @return array<int, string> */
    #[Computed]
    public function employeeNames(): array
    {
        return array_map(fn ($r) => $r['employee'], $this->roster());
    }

    /**
     * The seven day columns for the active week: key + display date.
     *
     * @return array<int, array{key:string, dayNum:string, monShort:string, isToday:bool}>
     */
    #[Computed]
    public function weekDays(): array
    {
        $start = Carbon::now()->startOfWeek(Carbon::SUNDAY)->addWeeks($this->weekOffset);

        return array_map(function (int $i) use ($start) {
            $date = $start->copy()->addDays($i);

            return [
                'key' => self::DAYS[$i],
                'dayNum' => $date->isoFormat('D'),
                'monShort' => $date->isoFormat('MMM'),
                'isToday' => $date->isToday(),
            ];
        }, range(0, 6));
    }

    public function weekRangeLabel(): string
    {
        $start = Carbon::now()->startOfWeek(Carbon::SUNDAY)->addWeeks($this->weekOffset);
        $end = $start->copy()->addDays(6);

        return $start->isoFormat('D MMM').' – '.$end->isoFormat('D MMM YYYY');
    }

    /** @return array{count:int, hours:float} */
    #[Computed]
    public function summary(): array
    {
        $shifts = $this->shifts();

        return [
            'count' => count($shifts),
            'hours' => array_sum(array_map(fn ($s) => $this->shiftHours($s['start'], $s['end']), $shifts)),
        ];
    }

    public function openCreate(string $employee = '', string $day = ''): void
    {
        $this->reset(['editingUuid', 'form_employee', 'form_day', 'form_start', 'form_end']);
        $this->form_employee = $employee;
        $this->form_day = $day;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $uuid): void
    {
        $s = collect($this->shifts())->firstWhere('uuid', $uuid);
        if (! $s) {
            return;
        }
        $this->editingUuid = $uuid;
        $this->form_employee = (string) $s['employee'];
        $this->form_day = (string) $s['day'];
        $this->form_start = (string) $s['start'];
        $this->form_end = (string) $s['end'];
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_employee' => ['required', 'string', 'max:100'],
            'form_day' => ['required', Rule::in(self::DAYS)],
            'form_start' => ['required', 'date_format:H:i'],
            'form_end' => ['required', 'date_format:H:i'],
        ]);

        if ($this->shiftHours($this->form_start, $this->form_end) <= 0) {
            $this->addError('form_end', __('emp.schedule.endAfterStart'));

            return;
        }

        $payload = [
            'employee' => trim($this->form_employee),
            'day' => $this->form_day,
            'start' => $this->form_start,
            'end' => $this->form_end,
        ];

        if (! $this->usingFallback()) {
            $ok = $this->waqty(function () use ($payload) {
                $service = app(EmployeeHrService::class);
                $this->editingUuid
                    ? $service->updateShift($this->editingUuid, $payload)
                    : $service->createShift($payload);

                return true;
            }, __('emp.schedule.saveFailed'));

            if (! $ok) {
                return;
            }

            $this->loaded = null;
            unset($this->shifts, $this->roster, $this->summary, $this->employeeNames);
        } elseif ($this->editingUuid) {
            if (str_starts_with($this->editingUuid, 'local-')) {
                foreach ($this->addedShifts as $i => $a) {
                    if (($a['uuid'] ?? null) === $this->editingUuid) {
                        $this->addedShifts[$i] = array_merge($a, $payload);
                    }
                }
            } else {
                $this->editedShifts[$this->editingUuid] = $payload;
            }
            unset($this->shifts, $this->roster, $this->summary, $this->employeeNames);
        } else {
            $this->addedShifts[] = array_merge($payload, ['uuid' => 'local-'.count($this->addedShifts)]);
            unset($this->shifts, $this->roster, $this->summary, $this->employeeNames);
        }

        $this->showForm = false;
        $this->editingUuid = null;
        $this->dispatch('notify', type: 'success', message: __('emp.schedule.saved'));
    }

    public function render()
    {
        return view('livewire.employees.schedule');
    }

    /**
     * Shape a raw API/sample row into the fields the grid renders.
     *
     * @param  array<string, mixed>  $r
     * @return array<string, mixed>
     */
    private function normalize(array $r): array
    {
        $day = strtolower((string) ($r['day'] ?? ''));

        return [
            'uuid' => (string) ($r['uuid'] ?? $r['id'] ?? ''),
            'employee' => (string) ($r['employee'] ?? $r['employee_name'] ?? $r['name'] ?? ''),
            'day' => in_array($day, self::DAYS, true) ? $day : '',
            'start' => (string) ($r['start'] ?? $r['from'] ?? $r['start_time'] ?? ''),
            'end' => (string) ($r['end'] ?? $r['to'] ?? $r['end_time'] ?? ''),
        ];
    }

    /** Duration in hours between two HH:MM marks, wrapping past midnight. */
    private function shiftHours(string $from, string $to): float
    {
        if ($from === '' || $to === '') {
            return 0.0;
        }
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
     * Local Arabic sample roster with a spread of weekly shifts for graceful
     * degradation. Day keys stay English; times are HH:MM.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'SH1', 'employee' => 'سارة أحمد', 'day' => 'sun', 'start' => '10:00', 'end' => '18:00'],
            ['uuid' => 'SH2', 'employee' => 'سارة أحمد', 'day' => 'mon', 'start' => '10:00', 'end' => '18:00'],
            ['uuid' => 'SH3', 'employee' => 'سارة أحمد', 'day' => 'wed', 'start' => '12:00', 'end' => '20:00'],
            ['uuid' => 'SH4', 'employee' => 'سارة أحمد', 'day' => 'thu', 'start' => '10:00', 'end' => '16:00'],
            ['uuid' => 'SH5', 'employee' => 'منى عادل', 'day' => 'sat', 'start' => '12:00', 'end' => '20:00'],
            ['uuid' => 'SH6', 'employee' => 'منى عادل', 'day' => 'sun', 'start' => '12:00', 'end' => '20:00'],
            ['uuid' => 'SH7', 'employee' => 'منى عادل', 'day' => 'tue', 'start' => '12:00', 'end' => '20:00'],
            ['uuid' => 'SH8', 'employee' => 'منى عادل', 'day' => 'thu', 'start' => '14:00', 'end' => '22:00'],
            ['uuid' => 'SH9', 'employee' => 'خالد حسن', 'day' => 'mon', 'start' => '09:00', 'end' => '17:00'],
            ['uuid' => 'SH10', 'employee' => 'خالد حسن', 'day' => 'tue', 'start' => '09:00', 'end' => '17:00'],
            ['uuid' => 'SH11', 'employee' => 'خالد حسن', 'day' => 'wed', 'start' => '09:00', 'end' => '17:00'],
            ['uuid' => 'SH12', 'employee' => 'خالد حسن', 'day' => 'sat', 'start' => '10:00', 'end' => '18:00'],
            ['uuid' => 'SH13', 'employee' => 'ياسمين فاروق', 'day' => 'sun', 'start' => '11:00', 'end' => '19:00'],
            ['uuid' => 'SH14', 'employee' => 'ياسمين فاروق', 'day' => 'wed', 'start' => '11:00', 'end' => '19:00'],
            ['uuid' => 'SH15', 'employee' => 'ياسمين فاروق', 'day' => 'fri', 'start' => '14:00', 'end' => '22:00'],
            ['uuid' => 'SH16', 'employee' => 'طارق سامي', 'day' => 'thu', 'start' => '16:00', 'end' => '23:00'],
            ['uuid' => 'SH17', 'employee' => 'طارق سامي', 'day' => 'fri', 'start' => '16:00', 'end' => '23:00'],
            ['uuid' => 'SH18', 'employee' => 'طارق سامي', 'day' => 'sat', 'start' => '14:00', 'end' => '22:00'],
        ];
    }
}
