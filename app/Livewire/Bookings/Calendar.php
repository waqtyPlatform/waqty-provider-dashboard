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
#[Title('Calendar — Waqty')]
class Calendar extends Component
{
    /** 30-minute slots, 09:00 → 20:30 (24 rows). */
    public const SLOT_MINUTES = 30;

    public const SLOT_PX = 64;

    public const BASE_HOUR = 9;

    #[Url(as: 'date')]
    public string $date = '';

    /** day | week | month */
    #[Url]
    public string $view = 'day';

    public string $statusFilter = 'all';

    public string $employeeFilter = 'all';

    private bool $fallbackUsed = false;

    public function mount(): void
    {
        if ($this->date === '') {
            $this->date = Carbon::today()->toDateString();
        }
    }

    public function prev(): void
    {
        $d = Carbon::parse($this->date);
        $moved = match ($this->view) {
            'week' => $d->subWeek(),
            'month' => $d->subMonthNoOverflow(),
            default => $d->subDay(),
        };
        $this->date = $moved->toDateString();
        $this->resetData();
    }

    public function next(): void
    {
        $d = Carbon::parse($this->date);
        $moved = match ($this->view) {
            'week' => $d->addWeek(),
            'month' => $d->addMonthNoOverflow(),
            default => $d->addDay(),
        };
        $this->date = $moved->toDateString();
        $this->resetData();
    }

    public function today(): void
    {
        $this->date = Carbon::today()->toDateString();
        $this->resetData();
    }

    public function updatedView(): void
    {
        $this->resetData();
    }

    public function updatedStatusFilter(): void
    {
        unset($this->blocks, $this->queueMap);
    }

    public function updatedEmployeeFilter(): void
    {
        unset($this->blocks, $this->queueMap);
    }

    private function resetData(): void
    {
        unset($this->bookings, $this->blocks, $this->queueMap, $this->summary, $this->rangeBookings);
    }

    /** The visible date window [start, end] for the active view. @return array{0:string, 1:string} */
    private function visibleRange(): array
    {
        $d = Carbon::parse($this->date);

        return match ($this->view) {
            'week' => [$d->copy()->startOfWeek()->toDateString(), $d->copy()->endOfWeek()->toDateString()],
            'month' => [$d->copy()->startOfMonth()->startOfWeek()->toDateString(), $d->copy()->endOfMonth()->endOfWeek()->toDateString()],
            default => [$this->date, $this->date],
        };
    }

    /** The 7 dates of the week containing $date. @return array<int, string> */
    public function weekDays(): array
    {
        $start = Carbon::parse($this->date)->startOfWeek();

        return array_map(fn ($i) => $start->copy()->addDays($i)->toDateString(), range(0, 6));
    }

    /**
     * Month grid cells (weeks × 7), each covering one date.
     *
     * @return array<int, array{date:string, day:int, inMonth:bool, isToday:bool}>
     */
    public function monthCells(): array
    {
        $ref = Carbon::parse($this->date);
        $cursor = $ref->copy()->startOfMonth()->startOfWeek();
        $end = $ref->copy()->endOfMonth()->endOfWeek();
        $today = Carbon::today()->toDateString();

        $cells = [];
        while ($cursor->lte($end)) {
            $cells[] = [
                'date' => $cursor->toDateString(),
                'day' => $cursor->day,
                'inMonth' => $cursor->month === $ref->month,
                'isToday' => $cursor->toDateString() === $today,
            ];
            $cursor->addDay();
        }

        return $cells;
    }

    /**
     * Bookings across the visible range, grouped by date (week/month views).
     *
     * @return array<string, array<int, BookingData>>
     */
    #[Computed]
    public function rangeBookings(): array
    {
        [$start, $end] = $this->visibleRange();

        try {
            $all = app(BookingService::class)->list(['from_date' => $start, 'to_date' => $end, 'per_page' => 300]);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $all = [];
            for ($d = Carbon::parse($start); $d->lte(Carbon::parse($end)); $d->addDay()) {
                foreach (BookingSamples::forDate($d->toDateString()) as $a) {
                    $all[] = BookingData::from($a);
                }
            }
        }

        $grouped = [];
        foreach ($all as $b) {
            if ($this->statusFilter !== 'all' && $b->status !== $this->statusFilter) {
                continue;
            }
            if ($this->employeeFilter !== 'all' && $b->employee_uuid !== $this->employeeFilter) {
                continue;
            }
            if (blank($b->booking_date)) {
                continue;
            }
            $grouped[$b->booking_date][] = $b;
        }

        foreach ($grouped as &$list) {
            usort($list, fn (BookingData $a, BookingData $b) => (string) $a->hhmm() <=> (string) $b->hhmm());
        }

        return $grouped;
    }

    /** @return array<int, string> */
    public function timeSlots(): array
    {
        $slots = [];
        $start = self::BASE_HOUR * 60;
        for ($i = 0; $i < 24; $i++) {
            $m = $start + $i * self::SLOT_MINUTES;
            $slots[] = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
        }

        return $slots;
    }

    /** @return array<int, EmployeeData> active employees = calendar columns */
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

    /** @return array<int, BookingData> */
    #[Computed]
    public function bookings(): array
    {
        try {
            return app(BookingService::class)->list(['booking_date' => $this->date, 'per_page' => 100]);
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
     * Positioned grid blocks (port of visitsToBlocks). One booking = one block.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function blocks(): array
    {
        $slots = $this->timeSlots();
        $employees = $this->employees();
        $blocks = [];

        foreach ($this->bookings() as $b) {
            if ($this->statusFilter !== 'all' && $b->status !== $this->statusFilter) {
                continue;
            }

            $empIndex = $this->matchEmployee($b, $employees);
            if ($empIndex === -1) {
                continue;
            }
            if ($this->employeeFilter !== 'all' && ($employees[$empIndex]->uuid ?? '') !== $this->employeeFilter) {
                continue;
            }

            $startSlot = array_search($b->hhmm(), $slots, true);
            if ($startSlot === false) {
                continue;
            }

            $blocks[] = [
                'empIndex' => $empIndex,
                'startSlot' => $startSlot,
                'span' => max(1, (int) round($b->durationMinutes() / self::SLOT_MINUTES)),
                'client' => $b->clientName(),
                'service' => $b->serviceName(),
                'status' => $b->status,
                'color' => $b->statusEnum()->color(),
                'uuid' => $b->uuid,
                'start' => $b->hhmm(),
                'end' => $b->endHhmm() ?? ($slots[$startSlot + max(1, (int) round($b->durationMinutes() / self::SLOT_MINUTES))] ?? '21:00'),
                'price' => $b->price,
            ];
        }

        return $blocks;
    }

    /**
     * Daily queue numbers per employee (sorted by startSlot, excludes cancelled).
     *
     * @return array<string, int> block uuid => queue number
     */
    #[Computed]
    public function queueMap(): array
    {
        $byEmp = [];
        foreach ($this->blocks() as $b) {
            if ($b['status'] === 'cancelled') {
                continue;
            }
            $byEmp[$b['empIndex']][] = $b;
        }

        $map = [];
        foreach ($byEmp as $list) {
            usort($list, fn ($a, $b) => $a['startSlot'] <=> $b['startSlot']);
            foreach ($list as $i => $b) {
                $map[$b['uuid']] = $i + 1;
            }
        }

        return $map;
    }

    /** @return array{total:int, confirmed:int, completed:int, cancelled:int, revenue:int} */
    #[Computed]
    public function summary(): array
    {
        $bookings = $this->bookings();
        $count = fn (string $s) => count(array_filter($bookings, fn (BookingData $b) => $b->status === $s));

        return [
            'total' => count($bookings),
            'confirmed' => $count('confirmed'),
            'completed' => $count('completed'),
            'cancelled' => $count('cancelled'),
            'revenue' => array_sum(array_map(
                fn (BookingData $b) => $b->status === 'cancelled' ? 0 : (int) $b->price,
                $bookings,
            )),
        ];
    }

    /** @param array<int, EmployeeData> $employees */
    private function matchEmployee(BookingData $b, array $employees): int
    {
        foreach ($employees as $i => $e) {
            if ($e->uuid && $e->uuid === $b->employee_uuid) {
                return $i;
            }
        }

        // First-name fallback (source parity for mock/live id mismatches).
        $name = $b->employeeName();
        if ($name) {
            $first = mb_strtolower(explode(' ', trim($name))[0]);
            foreach ($employees as $i => $e) {
                if ($e->name && mb_strtolower(explode(' ', trim($e->name))[0]) === $first) {
                    return $i;
                }
            }
        }

        return -1;
    }

    public function render()
    {
        return view('livewire.bookings.calendar');
    }
}
