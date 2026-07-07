<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * Port of `DashboardSummary` (src/lib/api.ts) — GET /api/provider/dashboard/summary.
 * Money fields (total_revenue, revenue_by_day[].revenue, top_*[].revenue/spent)
 * are integer minor units. `*_trend` are signed percentages. top_* and the two
 * distribution arrays are kept as raw associative arrays (read via data_get).
 */
class DashboardSummaryData extends Data
{
    public function __construct(
        public int $total_revenue = 0,
        public int $total_bookings = 0,
        public int $new_clients = 0,
        public int $total_invoices = 0,
        public int $total_returns = 0,
        public float $revenue_trend = 0,
        public float $bookings_trend = 0,
        public float $clients_trend = 0,
        /** @var array<int, array{name:string, revenue:int, count:int}> */
        public array $top_services = [],
        /** @var array<int, array{name:string, revenue:int, bookings:int}> */
        public array $top_employees = [],
        /** @var array<int, array{name:string, visits:int, spent:int}> */
        public array $top_clients = [],
        /** @var array<int, array{status:string, count:int}> */
        public array $booking_status_distribution = [],
        /** @var array<int, array{date:string, revenue:int}> */
        public array $revenue_by_day = [],
        public float $occupancy_rate = 0,
    ) {}
}
