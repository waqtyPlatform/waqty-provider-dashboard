<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * A fingerprint / attendance area — /api/provider/settings/fingerprint-areas.
 * Maps an attendance zone to the biometric `device` name that covers it.
 */
class FingerprintAreaData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $name = null,
        public ?string $device = null,
        public bool $active = true,
    ) {}
}
