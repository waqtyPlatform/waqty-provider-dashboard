<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * Port of `ProviderDashboard` (src/lib/api.ts) — aggregated counts from
 * GET /api/provider/dashboard. Sub-structures are kept as arrays and read via
 * accessors so partial/absent responses never fatal.
 */
class DashboardData extends Data
{
    public function __construct(
        /** @var array<string, mixed> */
        public array $bookings = [],
        /** @var array<string, mixed> */
        public array $revenue = [],
        /** @var array<string, mixed> */
        public array $employees = [],
        /** @var array<string, mixed> */
        public array $branches = [],
        /** @var array<string, mixed> */
        public array $ratings = [],
        /** @var array<string, mixed> */
        public array $payments = [],
    ) {}

    public function bookingsTotal(): int
    {
        return (int) data_get($this->bookings, 'total', 0);
    }

    public function todayTotal(): int
    {
        return (int) data_get($this->bookings, 'today.total', 0);
    }

    /** @return array<string, int> */
    public function byStatus(): array
    {
        return array_map(fn ($v) => (int) $v, (array) data_get($this->bookings, 'by_status', []));
    }

    public function revenueTotal(): int
    {
        return (int) data_get($this->revenue, 'total', 0);
    }

    public function revenueToday(): int
    {
        return (int) data_get($this->revenue, 'today', 0);
    }

    public function employeesActive(): int
    {
        return (int) data_get($this->employees, 'active', 0);
    }

    public function employeesTotal(): int
    {
        return (int) data_get($this->employees, 'total', 0);
    }

    public function branchesActive(): int
    {
        return (int) data_get($this->branches, 'active', 0);
    }

    public function ratingsAverage(): float
    {
        return round((float) data_get($this->ratings, 'average', 0), 1);
    }

    public function ratingsTotal(): int
    {
        return (int) data_get($this->ratings, 'total', 0);
    }
}
