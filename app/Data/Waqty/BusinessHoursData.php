<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * Port of `BusinessHours` (src/lib/api.ts) — GET/PUT /api/provider/settings/business-hours.
 * `day` is 0=Sunday … 6=Saturday.
 */
class BusinessHoursData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public int $day = 0,
        public ?string $open_time = '09:00',
        public ?string $close_time = '20:00',
        public ?string $break_start = null,
        public ?string $break_end = null,
        public bool $is_closed = false,
    ) {}
}
