<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * Port of `CustomerStatement` (src/lib/api.ts). amount/balance are integer
 * minor units. Endpoint: /api/provider/customers/{uuid}/statements.
 */
class CustomerStatementData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $customer_uuid = null,
        public string $type = 'debit',
        public int $amount = 0,
        public int $balance = 0,
        public ?string $description = null,
        public ?string $reference_type = null,
        public ?string $reference_uuid = null,
        public ?string $created_at = null,
    ) {}

    public function isCredit(): bool
    {
        return $this->type === 'credit';
    }
}
