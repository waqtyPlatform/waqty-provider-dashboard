<?php

declare(strict_types=1);

namespace App\Services\Waqty;

use App\Data\Waqty\TransactionData;

/**
 * Port of `transactionApi` (src/lib/api.ts) — /api/provider/transactions.
 */
class TransactionService
{
    public function __construct(private readonly WaqtyApiClient $api) {}

    /**
     * @param  array<string, mixed>  $filters  type|status|search|per_page
     * @return array<int, TransactionData>
     */
    public function transactions(array $filters = []): array
    {
        return TransactionData::collect($this->rows($this->api->get('/api/provider/transactions', $filters)));
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): TransactionData
    {
        return TransactionData::from($this->api->post('/api/provider/transactions', $data));
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
