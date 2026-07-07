<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * Port of `PaymentMethod` (src/lib/api.ts) — /api/provider/settings/payment-methods.
 * `fee_fixed` is integer minor units; `fee_percentage` is a percent.
 */
class PaymentMethodData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $name = null,
        public string $type = 'cash',
        public float $fee_percentage = 0,
        public int $fee_fixed = 0,
        public bool $active = true,
    ) {}
}
