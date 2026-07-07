<?php

declare(strict_types=1);

namespace App\Services\Waqty;

use App\Data\Waqty\ExpenseData;

/**
 * Port of `expenseApi` (src/lib/api.ts) — /api/provider/expenses.
 */
class ExpenseService
{
    public function __construct(private readonly WaqtyApiClient $api) {}

    /**
     * @param  array<string, mixed>  $filters  category|status|from|to|per_page
     * @return array<int, ExpenseData>
     */
    public function expenses(array $filters = []): array
    {
        return ExpenseData::collect($this->rows($this->api->get('/api/provider/expenses', $filters)));
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): ExpenseData
    {
        return ExpenseData::from($this->api->post('/api/provider/expenses', $data));
    }

    /** @param array<string, mixed> $data */
    public function update(string $uuid, array $data): ExpenseData
    {
        return ExpenseData::from($this->api->put("/api/provider/expenses/{$uuid}", $data));
    }

    public function delete(string $uuid): void
    {
        $this->api->delete("/api/provider/expenses/{$uuid}");
    }

    public function approve(string $uuid): void
    {
        $this->api->patch("/api/provider/expenses/{$uuid}/approve");
    }

    public function reject(string $uuid): void
    {
        $this->api->patch("/api/provider/expenses/{$uuid}/reject");
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
