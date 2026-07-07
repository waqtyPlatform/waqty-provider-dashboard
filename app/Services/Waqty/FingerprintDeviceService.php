<?php

declare(strict_types=1);

namespace App\Services\Waqty;

use App\Data\Waqty\FingerprintDeviceData;

/**
 * Fingerprint (biometric attendance) devices —
 * /api/provider/settings/fingerprint-devices.
 */
class FingerprintDeviceService
{
    private const ENDPOINT = '/api/provider/settings/fingerprint-devices';

    public function __construct(private readonly WaqtyApiClient $api) {}

    /** @return array<int, FingerprintDeviceData> */
    public function list(): array
    {
        return FingerprintDeviceData::collect($this->rows($this->api->get(self::ENDPOINT)));
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): FingerprintDeviceData
    {
        return FingerprintDeviceData::from($this->api->post(self::ENDPOINT, $data));
    }

    /** @param array<string, mixed> $data */
    public function update(string $uuid, array $data): FingerprintDeviceData
    {
        return FingerprintDeviceData::from($this->api->put(self::ENDPOINT."/{$uuid}", $data));
    }

    public function delete(string $uuid): void
    {
        $this->api->delete(self::ENDPOINT."/{$uuid}");
    }

    /** @return array<int, mixed> */
    private function rows(mixed $data): array
    {
        if (is_array($data) && isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }

        return is_array($data) ? $data : [];
    }
}
