<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * Port of `BookingActivity` (src/lib/api.ts) — one entry from
 * GET /api/provider/bookings/{uuid}/activities.
 */
class BookingActivityData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $event = null,
        public ?string $label = null,
        public ?string $actor_type = null,
        public ?string $actor_name = null,
        /** @var array<string, mixed>|null */
        public ?array $metadata = null,
        public ?string $created_at = null,
    ) {}
}
