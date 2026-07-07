<?php

declare(strict_types=1);

namespace App\Services\Waqty;

use App\Data\Waqty\FingerprintAreaData;

/**
 * Fingerprint areas CRUD — /api/provider/settings/fingerprint-areas.
 * Attendance zones each mapped to a biometric device name.
 */
class FingerprintAreaService
{
    public function __construct(private readonly WaqtyApiClient $api) {}

    /** @return array<int, FingerprintAreaData> */
    public function list(): array
    {
        return FingerprintAreaData::collect($this->rows($this->api->get('/api/provider/settings/fingerprint-areas')));
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): FingerprintAreaData
    {
        return FingerprintAreaData::from($this->api->post('/api/provider/settings/fingerprint-areas', $data));
    }

    /** @param array<string, mixed> $data */
    public function update(string $uuid, array $data): FingerprintAreaData
    {
        return FingerprintAreaData::from($this->api->put("/api/provider/settings/fingerprint-areas/{$uuid}", $data));
    }

    public function delete(string $uuid): void
    {
        $this->api->delete("/api/provider/settings/fingerprint-areas/{$uuid}");
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
