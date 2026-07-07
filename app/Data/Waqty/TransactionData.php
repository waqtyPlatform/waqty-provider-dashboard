<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * Port of `Transaction` (src/lib/api.ts). `amount` is integer minor units.
 * Endpoint: /api/provider/transactions.
 */
class TransactionData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public string $type = 'sale',
        public int $amount = 0,
        public ?string $payment_method = null,
        public string $status = 'completed',
        /** @var array<string, mixed>|null */
        public ?array $customer = null,
        /** @var array<string, mixed>|null */
        public ?array $employee = null,
        /** @var array<string, mixed>|null */
        public ?array $branch = null,
        public ?string $booking_uuid = null,
        public ?string $notes = null,
        public ?string $reference_number = null,
        public ?string $created_at = null,
    ) {}

    public function customerName(): ?string
    {
        return data_get($this->customer, 'name');
    }

    public function employeeName(): ?string
    {
        return data_get($this->employee, 'name');
    }

    /** Refunds reduce net revenue. */
    public function isRefund(): bool
    {
        return in_array($this->type, ['refund'], true);
    }
}
