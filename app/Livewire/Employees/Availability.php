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
#[Title('Availability — Waqty')]
class Availability extends Component
{
    use HandlesWaqtyErrors;

    public string $branchFilter = 'all';

    public string $employeeFilter = 'all';

    /** @var array<int, array<string, mixed>>|null per-request memo */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    /** @return array<int, array<string, mixed>> */
    private function source(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $rows = app(EmployeeHrService::class)->availability();
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
        $branch = $this->branchFilter;
        $employee = $this->employeeFilter;

        return array_values(array_filter($this->source(), function (array $r) use ($branch, $employee) {
            return ($branch === 'all' || $r['branch'] === $branch)
                && ($employee === 'all' || $r['employee'] === $employee);
        }));
    }

    /** @return array{available:int, onLeave:int, total:int} */
    #[Computed]
    public function kpis(): array
    {
        $all = $this->source();

        return [
            'available' => count(array_filter($all, fn (array $r) => $r['status'] === 'available')),
            'onLeave' => count(array_filter($all, fn (array $r) => $r['status'] === 'on_leave')),
            'total' => count($all),
        ];
    }

    /** Distinct branch names for the branch <select>. @return array<int, string> */
    #[Computed]
    public function branchOptions(): array
    {
        return array_values(array_unique(array_filter(array_map(fn (array $r) => $r['branch'], $this->source()))));
    }

    /** Distinct employee names for the employee <select>. @return array<int, string> */
    #[Computed]
    public function employeeOptions(): array
    {
        return array_values(array_unique(array_filter(array_map(fn (array $r) => $r['employee'], $this->source()))));
    }

    public function render()
    {
        return view('livewire.employees.availability');
    }

    /**
     * Shape a raw API/sample row into the fields this screen renders,
     * deriving the weekly day count and total hours from the slots.
     *
     * @param  array<string, mixed>  $r
     * @return array<string, mixed>
     */
    private function normalize(array $r): array
    {
        $slots = [];
        foreach ((array) ($r['slots'] ?? $r['schedule'] ?? []) as $s) {
            if (! is_array($s)) {
                continue;
            }
            $day = (string) ($s['day'] ?? '');
            $from = (string) ($s['from'] ?? $s['start'] ?? '');
            $to = (string) ($s['to'] ?? $s['end'] ?? '');
            if ($day === '' || $from === '' || $to === '') {
                continue;
            }
            $slots[] = ['day' => $day, 'from' => $from, 'to' => $to];
        }

        $hours = array_sum(array_map(fn ($s) => $this->slotHours($s['from'], $s['to']), $slots));

        $status = (string) ($r['status'] ?? 'available');

        return [
            'uuid' => (string) ($r['uuid'] ?? $r['id'] ?? ''),
            'employee' => (string) ($r['employee'] ?? $r['employee_name'] ?? $r['name'] ?? ''),
            'branch' => (string) ($r['branch'] ?? $r['branch_name'] ?? ''),
            'status' => in_array($status, ['available', 'on_leave', 'off'], true) ? $status : 'available',
            'slots' => $slots,
            'days' => count($slots),
            'hours' => $hours,
        ];
    }

    /** Duration in hours between two HH:MM marks, wrapping past midnight. */
    private function slotHours(string $from, string $to): float
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
     * Local Arabic sample availability with weekly slots for graceful degradation.
     * Status enum values (available|on_leave|off) stay English; day keys stay English.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'AV1', 'employee' => 'سارة أحمد', 'branch' => 'وسط البلد', 'status' => 'available', 'slots' => [
                ['day' => 'sun', 'from' => '10:00', 'to' => '18:00'],
                ['day' => 'mon', 'from' => '10:00', 'to' => '18:00'],
                ['day' => 'tue', 'from' => '10:00', 'to' => '18:00'],
                ['day' => 'wed', 'from' => '10:00', 'to' => '18:00'],
                ['day' => 'thu', 'from' => '10:00', 'to' => '16:00'],
            ]],
            ['uuid' => 'AV2', 'employee' => 'منى عادل', 'branch' => 'وسط البلد', 'status' => 'available', 'slots' => [
                ['day' => 'sat', 'from' => '12:00', 'to' => '20:00'],
                ['day' => 'sun', 'from' => '12:00', 'to' => '20:00'],
                ['day' => 'mon', 'from' => '12:00', 'to' => '20:00'],
                ['day' => 'wed', 'from' => '12:00', 'to' => '20:00'],
            ]],
            ['uuid' => 'AV3', 'employee' => 'خالد حسن', 'branch' => 'القاهرة الجديدة', 'status' => 'on_leave', 'slots' => [
                ['day' => 'mon', 'from' => '09:00', 'to' => '17:00'],
                ['day' => 'tue', 'from' => '09:00', 'to' => '17:00'],
                ['day' => 'wed', 'from' => '09:00', 'to' => '17:00'],
            ]],
            ['uuid' => 'AV4', 'employee' => 'ياسمين فاروق', 'branch' => 'القاهرة الجديدة', 'status' => 'available', 'slots' => [
                ['day' => 'sun', 'from' => '11:00', 'to' => '19:00'],
                ['day' => 'tue', 'from' => '11:00', 'to' => '19:00'],
                ['day' => 'thu', 'from' => '11:00', 'to' => '19:00'],
                ['day' => 'sat', 'from' => '14:00', 'to' => '22:00'],
            ]],
            ['uuid' => 'AV5', 'employee' => 'طارق سامي', 'branch' => 'وسط البلد', 'status' => 'off', 'slots' => [
                ['day' => 'thu', 'from' => '16:00', 'to' => '23:00'],
                ['day' => 'fri', 'from' => '16:00', 'to' => '23:00'],
            ]],
        ];
    }
}
