<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * A bookable physical resource — chair / room / equipment.
 * /api/provider/settings/resources. `status` is 'active' | 'maintenance'.
 */
class ResourceData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $name = null,
        public string $type = 'chair',
        public int $capacity = 1,
        public string $status = 'active',
    ) {}
}
