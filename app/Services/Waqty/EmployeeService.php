<?php

declare(strict_types=1);

namespace App\Services\Waqty;

use App\Data\Waqty\EmployeeData;

/**
 * Port of `providerApi` employee methods (src/lib/api.ts:993-1002).
 * Endpoints under /api/provider/employees*.
 */
class EmployeeService
{
    public function __construct(private readonly WaqtyApiClient $api) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, EmployeeData>
     */
    public function employees(array $filters = []): array
    {
        return EmployeeData::collect($this->rows($this->api->get('/api/provider/employees', $filters)));
    }

    public function employee(string $uuid): EmployeeData
    {
        return EmployeeData::from($this->api->get("/api/provider/employees/{$uuid}"));
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): EmployeeData
    {
        return EmployeeData::from($this->api->post('/api/provider/employees', $data));
    }

    /** @param array<string, mixed> $data */
    public function update(string $uuid, array $data): EmployeeData
    {
        return EmployeeData::from($this->api->put("/api/provider/employees/{$uuid}", $data));
    }

    public function delete(string $uuid): void
    {
        $this->api->delete("/api/provider/employees/{$uuid}");
    }

    public function toggleActive(string $uuid, bool $active): void
    {
        $this->api->patch("/api/provider/employees/{$uuid}/active", ['active' => $active]);
    }

    public function toggleBlock(string $uuid, bool $blocked): void
    {
        $this->api->patch("/api/provider/employees/{$uuid}/block", ['blocked' => $blocked]);
    }

    /**
     * Normalise a response that may be a bare list or a {data, meta} envelope.
     *
     * @return array<int, mixed>
     */
    private function rows(mixed $data): array
    {
        if (is_array($data) && isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }

        return is_array($data) ? $data : [];
    }
}
