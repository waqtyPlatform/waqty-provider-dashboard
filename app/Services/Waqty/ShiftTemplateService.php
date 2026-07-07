<?php

declare(strict_types=1);

namespace App\Services\Waqty;

use App\Data\Waqty\ShiftTemplateData;

/**
 * Reusable shift templates — /api/provider/shift-templates.
 * CRUD over the provider surface via {@see WaqtyApiClient}.
 */
class ShiftTemplateService
{
    public function __construct(private readonly WaqtyApiClient $api) {}

    /** @return array<int, ShiftTemplateData> */
    public function list(): array
    {
        return ShiftTemplateData::collect($this->rows($this->api->get('/api/provider/shift-templates')));
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): ShiftTemplateData
    {
        return ShiftTemplateData::from($this->api->post('/api/provider/shift-templates', $data));
    }

    /** @param array<string, mixed> $data */
    public function update(string $uuid, array $data): ShiftTemplateData
    {
        return ShiftTemplateData::from($this->api->put("/api/provider/shift-templates/{$uuid}", $data));
    }

    public function delete(string $uuid): void
    {
        $this->api->delete("/api/provider/shift-templates/{$uuid}");
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
