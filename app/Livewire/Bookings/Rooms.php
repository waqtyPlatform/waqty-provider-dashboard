<?php

declare(strict_types=1);

namespace App\Livewire\Bookings;

use App\Data\Waqty\BookingData;
use App\Services\Waqty\BookingService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\BookingSamples;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Rooms board — a day view that lays each booking out in a fixed set of rooms.
 * Bookings carry no room field, so rooms are assigned deterministically from the
 * booking uuid (mock parity). One booking = one positioned block.
 */
#[Layout('components.layouts.app')]
#[Title('Rooms — Waqty')]
class Rooms extends Component
{
    /** 30-minute slots, 09:00 → 20:30 (24 rows). */
    public const SLOT_MINUTES = 30;

    public const SLOT_PX = 64;

    public const BASE_HOUR = 9;

    /** Fixed sample room list (no room field on bookings). */
    public const ROOMS = ['غرفة 1', 'غرفة 2', 'غرفة 3', 'كرسي 1'];

    #[Url(as: 'date')]
    public string $date = '';

    public bool $busyOnly = false;

    private bool $fallbackUsed = false;

    public function mount(): void
    {
        if ($this->date === '') {
            $this->date = Carbon::today()->toDateString();
        }
    }

    public function prevDay(): void
    {
        $this->date = Carbon::parse($this->date)->subDay()->toDateString();
        $this->resetData();
    }

    public function nextDay(): void
    {
        $this->date = Carbon::parse($this->date)->addDay()->toDateString();
        $this->resetData();
    }

    public function today(): void
    {
        $this->date = Carbon::today()->toDateString();
        $this->resetData();
    }

    private function resetData(): void
    {
        unset($this->bookings, $this->blocks);
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

    /** @return array<int, string> */
    public function rooms(): array
    {
        return self::ROOMS;
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
        $this->bookings();

        return $this->fallbackUsed;
    }

    /**
     * Positioned grid blocks. Each booking is assigned a room deterministically
     * from its uuid, then placed by start slot / duration span.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function blocks(): array
    {
        $slots = $this->timeSlots();
        $roomCount = count(self::ROOMS);
        $blocks = [];

        foreach ($this->bookings() as $b) {
            $startSlot = array_search($b->hhmm(), $slots, true);
            if ($startSlot === false) {
                continue;
            }

            $span = max(1, (int) round($b->durationMinutes() / self::SLOT_MINUTES));
            $roomIndex = abs(crc32((string) $b->uuid)) % $roomCount;

            $blocks[] = [
                'roomIndex' => $roomIndex,
                'startSlot' => $startSlot,
                'span' => $span,
                'client' => $b->clientName(),
                'service' => $b->serviceName(),
                'status' => $b->status,
                'color' => $b->statusEnum()->color(),
                'uuid' => $b->uuid,
                'start' => $b->hhmm(),
                'end' => $b->endHhmm() ?? ($slots[$startSlot + $span] ?? '21:00'),
            ];
        }

        return $blocks;
    }

    /**
     * Rooms to render — all rooms, or only those with at least one block when
     * the busy-only toggle is on.
     *
     * @return array<int, int> room indexes
     */
    #[Computed]
    public function visibleRooms(): array
    {
        $indexes = array_keys(self::ROOMS);

        if (! $this->busyOnly) {
            return $indexes;
        }

        $busy = array_unique(array_map(fn ($blk) => $blk['roomIndex'], $this->blocks()));

        return array_values(array_filter($indexes, fn ($i) => in_array($i, $busy, true)));
    }

    public function render()
    {
        return view('livewire.bookings.rooms');
    }
}
