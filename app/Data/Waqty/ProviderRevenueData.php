<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * Port of `ProviderRevenue` (src/lib/api.ts) — GET /api/provider/revenue.
 * All revenue figures are integer minor units.
 */
class ProviderRevenueData extends Data
{
    public function __construct(
        public int $total_revenue = 0,
        /** @var array<int, array{branch_uuid?:string, branch_name?:string, revenue?:int}> */
        public array $by_branch = [],
        /** @var array<int, array{employee_uuid?:string, employee_name?:string, revenue?:int}> */
        public array $by_employee = [],
    ) {}
}
