<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;

/**
 * Port of `ProviderClient` (src/lib/api.ts) — the real-API aggregated client
 * row from GET /api/provider/clients.
 */
class ClientAccountData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $phone = null,
        public int $total_bookings = 0,
        public ?string $last_booking_date = null,
    ) {}

    /** Whole days since the last booking, or null if never booked. */
    public function daysSince(): ?int
    {
        if (blank($this->last_booking_date)) {
            return null;
        }

        return (int) Carbon::parse($this->last_booking_date)->startOfDay()->diffInDays(Carbon::today(), absolute: true);
    }

    public function needsFollowUp(): bool
    {
        $days = $this->daysSince();

        return $days !== null && $days > 14;
    }
}
