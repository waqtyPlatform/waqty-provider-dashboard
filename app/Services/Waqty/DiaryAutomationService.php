<?php

declare(strict_types=1);

namespace App\Services\Waqty;

use App\Data\Waqty\DiaryAutomationData;

/**
 * Diary automations (src/lib/api.ts settingsApi) —
 * /api/provider/settings/diary-automations.
 */
class DiaryAutomationService
{
    public function __construct(private readonly WaqtyApiClient $api) {}

    /** @return array<int, DiaryAutomationData> */
    public function list(): array
    {
        return DiaryAutomationData::collect($this->rows($this->api->get('/api/provider/settings/diary-automations')));
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): DiaryAutomationData
    {
        return DiaryAutomationData::from($this->api->post('/api/provider/settings/diary-automations', $data));
    }

    /** @param array<string, mixed> $data */
    public function update(string $uuid, array $data): DiaryAutomationData
    {
        return DiaryAutomationData::from($this->api->put("/api/provider/settings/diary-automations/{$uuid}", $data));
    }

    public function delete(string $uuid): void
    {
        $this->api->delete("/api/provider/settings/diary-automations/{$uuid}");
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
