<?php

declare(strict_types=1);

namespace App\Data\Waqty;

use Spatie\LaravelData\Data;

/**
 * A biometric attendance device — /api/provider/settings/fingerprint-devices.
 */
class FingerprintDeviceData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $name = null,
        public ?string $ip_address = null,
        public int $port = 4370,
        public bool $active = true,
    ) {}
}
