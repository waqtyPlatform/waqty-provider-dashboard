<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * Port of `ClientStatementRow` (src/lib/api.ts) — one aggregated ledger row
 * from GET /api/provider/clients/statements. Money fields (total_charged,
 * total_paid, outstanding) are integer minor units.
 */
class ClientStatementRowData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $phone = null,
        public int $total_bookings = 0,
        public int $completed_bookings = 0,
        public int $cancelled_bookings = 0,
        public int $total_charged = 0,
        public int $total_paid = 0,
        public int $outstanding = 0,
        public ?string $last_booking_date = null,
    ) {}
}
