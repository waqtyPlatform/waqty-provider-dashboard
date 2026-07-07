<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

class CustomerGroupData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $name = null,
        public ?float $discount_percentage = null,
        public ?string $color = null,
        public ?string $description = null,
        public ?int $customers_count = null,
    ) {}
}
