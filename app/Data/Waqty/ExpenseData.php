<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * Port of `Expense` (src/lib/api.ts). `amount` is integer minor units.
 * Endpoint: /api/provider/expenses.
 */
class ExpenseData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $category = null,
        public ?string $vendor = null,
        public ?string $description = null,
        public int $amount = 0,
        /** @var array<string, mixed>|null */
        public ?array $branch = null,
        public string $status = 'pending',
        public ?string $approved_by = null,
        public ?string $receipt_url = null,
        public ?string $date = null,
        public ?string $payment_method = null,
        public ?string $created_at = null,
    ) {}
}
