<?php

declare(strict_types=1);

namespace App\Services\Waqty;

use App\Data\Waqty\ShiftAutomationData;

/**
 * Shift automations CRUD — /api/provider/settings/shift-automations.
 * UI-only clone; the Livewire component provides the sample-data fallback.
 */
class ShiftAutomationService
{
    public function __construct(private readonly WaqtyApiClient $api) {}

    /** @return array<int, ShiftAutomationData> */
    public function list(): array
    {
        return ShiftAutomationData::collect($this->rows($this->api->get('/api/provider/settings/shift-automations')));
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): ShiftAutomationData
    {
        return ShiftAutomationData::from($this->api->post('/api/provider/settings/shift-automations', $data));
    }

    /** @param array<string, mixed> $data */
    public function update(string $uuid, array $data): ShiftAutomationData
    {
        return ShiftAutomationData::from($this->api->put("/api/provider/settings/shift-automations/{$uuid}", $data));
    }

    public function delete(string $uuid): void
    {
        $this->api->delete("/api/provider/settings/shift-automations/{$uuid}");
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
