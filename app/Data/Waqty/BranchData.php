<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * Port of the `Branch` interface (src/lib/api.ts). Kept lenient — most screens
 * only need uuid/name; geo/flags hydrate when the endpoint returns them.
 */
class BranchData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $name = null,
        public ?string $phone = null,
        public ?string $city_uuid = null,
        public ?string $city = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?int $geofence_radius = null,
        public bool $require_gps = false,
        public bool $active = true,
        public bool $is_main = false,
    ) {}
}
