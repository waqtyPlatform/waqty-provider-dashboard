<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * Port of `Return` (src/lib/api.ts). `amount` is integer minor units.
 * Endpoint: /api/provider/returns.
 */
class ReturnData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public string $type = 'cash_refund',
        public ?string $transaction_uuid = null,
        /** @var array<string, mixed>|null */
        public ?array $customer = null,
        public int $amount = 0,
        public ?string $reason = null,
        public string $status = 'pending',
        public ?string $approved_by = null,
        public ?string $created_at = null,
    ) {}

    public function customerName(): ?string
    {
        return data_get($this->customer, 'name');
    }
}
