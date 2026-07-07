<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * Port of the `ServiceCategory` interface (src/lib/api.ts).
 * Endpoint: /api/provider/settings/service-categories.
 */
class ServiceCategoryData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $name = null,
        public ?string $name_ar = null,
        public int $sort_order = 0,
        public int $services_count = 0,
        public bool $active = true,
        public ?string $color = null,
    ) {}
}
