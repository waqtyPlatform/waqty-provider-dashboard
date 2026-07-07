<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * Port of the `ServicePrice` interface (src/lib/api.ts). `price` is integer
 * minor units. Scope is determined by which of branch/employee/pricing-group
 * uuids are set (see ServiceCatalogService::resolveBasePrice).
 */
class ServicePriceData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $service_uuid = null,
        public ?string $branch_uuid = null,
        public ?string $employee_uuid = null,
        public ?string $pricing_group_uuid = null,
        public int $price = 0,
        public bool $active = true,
    ) {}
}
