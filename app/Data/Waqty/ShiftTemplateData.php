<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * A reusable staff shift pattern — /api/provider/shift-templates.
 * `start_time`/`end_time` are 24h wall-clock strings like "09:00".
 */
class ShiftTemplateData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $name = null,
        public ?string $start_time = null,
        public ?string $end_time = null,
        public bool $active = true,
    ) {}
}
