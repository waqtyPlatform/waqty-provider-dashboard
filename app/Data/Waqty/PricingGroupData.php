<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * A pricing group — /api/provider/pricing-groups.
 * Groups employees so a shared service-price tier applies across all of them.
 * `employees_count` is a read-only display value.
 */
class PricingGroupData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $name = null,
        public bool $active = true,
        public int $employees_count = 0,
    ) {}
}
