<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * A pre-defined petty-cash expense item — /api/provider/settings/petty-cash-items.
 * `default_amount` is integer minor units (100 = 1 EGP).
 */
class PettyCashItemData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $name = null,
        public string $category = 'administrative',
        public int $default_amount = 0,
        public bool $active = true,
    ) {}
}
