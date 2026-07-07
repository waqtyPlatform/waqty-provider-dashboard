<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * Port of `ReportData` (src/lib/api.ts) — GET /api/provider/reports/{type}.
 * `labels` index the x-axis; each `datasets` entry is {label, data[]};
 * `summary` holds headline scalars (money in minor units).
 */
class ReportSeriesData extends Data
{
    public function __construct(
        /** @var array<int, string> */
        public array $labels = [],
        /** @var array<int, array<string, mixed>> */
        public array $datasets = [],
        /** @var array<string, mixed> */
        public array $summary = [],
    ) {}
}
