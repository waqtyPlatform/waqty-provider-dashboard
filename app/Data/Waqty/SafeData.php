<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * A cash safe / drawer — /api/provider/settings/safes.
 * `balance` is integer minor units (100 = 1 EGP).
 */
class SafeData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $name = null,
        public ?string $branch = null,
        public int $balance = 0,
        public bool $active = true,
        public ?string $last_activity = null,
    ) {}
}
