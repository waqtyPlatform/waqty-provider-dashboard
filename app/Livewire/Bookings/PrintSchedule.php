<?php

declare(strict_types=1);

namespace App\Livewire\Bookings;

use App\Data\Waqty\BookingData;
use App\Data\Waqty\EmployeeData;
use App\Services\Waqty\BookingService;
use App\Services\Waqty\EmployeeService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\BookingSamples;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Employee Schedule — Waqty')]
class PrintSchedule extends Component
{
    #[Url(as: 'date')]
    public string $date = '';

    #[Url(as: 'emp')]
    public string $employeeFilter = 'all';

    private bool $fallbackUsed = false;

    public function mount(): void
    {
        if ($this->date === '') {
            $this->date = Carbon::today()->toDateString();
        }
    }

    /** @return array<int, EmployeeData> active employees = schedule groups */
    #[Computed]
    public function employees(): array
    {
        try {
            $all = app(EmployeeService::class)->employees();
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $all = array_map(fn ($a) => EmployeeData::from($a), BookingSamples::employees());
        }

        return array_values(array_filter($all, fn (EmployeeData $e) => $e->active && ! $e->blocked));
    }

    /** @return array<int, BookingData> bookings for the selected day */
    #[Computed]
    public function bookings(): array
    {
        try {
            return app(BookingService::class)->list(['booking_date' => $this->date, 'per_page' => 200]);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;

            return array_map(fn ($a) => BookingData::from($a), BookingSamples::forDate($this->date));
        }
    }

    public function usingFallback(): bool
    {
        $this->employees();
        $this->bookings();

        return $this->fallbackUsed;
    }

    /**
     * The day's bookings grouped per employee, each list sorted by start time.
     * Honours the optional employee filter; unmatched bookings fall into a
     * trailing "unassigned" group (only in the unfiltered view).
     *
     * @return array<int, array{employee: ?EmployeeData, bookings: array<int, BookingData>, count: int, revenue: int}>
     */
    public function schedule(): array
    {
        $employees = $this->employees();
        if ($this->employeeFilter !== 'all') {
            $employees = array_values(array_filter($employees, fn (EmployeeData $e) => $e->uuid === $this->employeeFilter));
        }

        $bookings = $this->bookings();
        $matched = [];
        $groups = [];

        foreach ($employees as $emp) {
            $rows = [];
            foreach ($bookings as $b) {
                if ($this->belongsTo($b, $emp)) {
                    $rows[] = $b;
                    $matched[(string) ($b->uuid ?? spl_object_id($b))] = true;
                }
            }
            $groups[] = $this->group($emp, $this->sortByTime($rows));
        }

        if ($this->employeeFilter === 'all') {
            $leftover = array_values(array_filter(
                $bookings,
                fn (BookingData $b) => ! isset($matched[(string) ($b->uuid ?? spl_object_id($b))]),
            ));
            if ($leftover !== []) {
                $groups[] = $this->group(null, $this->sortByTime($leftover));
            }
        }

        return $groups;
    }

    /**
     * @param  array<int, BookingData>  $rows
     * @return array{employee: ?EmployeeData, bookings: array<int, BookingData>, count: int, revenue: int}
     */
    private function group(?EmployeeData $emp, array $rows): array
    {
        return [
            'employee' => $emp,
            'bookings' => $rows,
            'count' => count($rows),
            'revenue' => array_sum(array_map(
                fn (BookingData $b) => $b->status === 'cancelled' ? 0 : (int) $b->price,
                $rows,
            )),
        ];
    }

    /**
     * @param  array<int, BookingData>  $rows
     * @return array<int, BookingData>
     */
    private function sortByTime(array $rows): array
    {
        usort($rows, fn (BookingData $a, BookingData $b) => (string) $a->hhmm() <=> (string) $b->hhmm());

        return $rows;
    }

    private function belongsTo(BookingData $b, EmployeeData $emp): bool
    {
        if ($emp->uuid && $emp->uuid === $b->employee_uuid) {
            return true;
        }

        // First-name fallback (source parity for mock/live id mismatches).
        $name = $b->employeeName();
        if ($name && $emp->name) {
            return mb_strtolower(explode(' ', trim($name))[0]) === mb_strtolower(explode(' ', trim($emp->name))[0]);
        }

        return false;
    }

    public function render()
    {
        return view('livewire.bookings.print');
    }
}
