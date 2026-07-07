<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * Port of the `Customer` interface (src/lib/api.ts). Money fields
 * (total_spent) are integer minor units.
 */
class CustomerData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $group_uuid = null,
        public ?CustomerGroupData $group = null,
        public bool $vip = false,
        public ?string $notes = null,
        public ?string $allergies = null,
        public ?string $medical_conditions = null,
        public ?string $medications = null,
        public int $total_visits = 0,
        public int $total_spent = 0,
        public ?string $last_visit = null,
    ) {}

    public function groupName(): string
    {
        return $this->group?->name ?? 'Regular';
    }
}
