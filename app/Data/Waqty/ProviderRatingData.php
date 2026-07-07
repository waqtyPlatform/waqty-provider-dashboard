<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * Port of `ProviderRating` (src/lib/api.ts) — a review row from
 * GET /api/provider/ratings. `status` is not always returned by the ratings
 * endpoint; kept nullable and defaulted for the moderation filters.
 */
class ProviderRatingData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public int $rating = 0,
        public ?string $comment = null,
        public bool $active = true,
        public ?string $status = 'published',
        /** @var array<string, mixed>|null {uuid,name} */
        public ?array $user = null,
        /** @var array<string, mixed>|null {uuid,booking_date} */
        public ?array $booking = null,
        public ?string $response = null,
        public ?string $created_at = null,
    ) {}

    public function customerName(): string
    {
        return (string) (data_get($this->user, 'name') ?? '—');
    }

    public function bookingDate(): ?string
    {
        return data_get($this->booking, 'booking_date');
    }
}
