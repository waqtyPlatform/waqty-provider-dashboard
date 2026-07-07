<?php

declare(strict_types=1);

namespace App\Services\Waqty;

use App\Data\Waqty\BookingData;
use App\Data\Waqty\ClientAccountData;
use App\Data\Waqty\ClientStatementRowData;

/**
 * Real-API client endpoints (src/lib/api.ts) — /api/provider/clients* and the
 * aggregated /clients/statements ledger. Distinct from CustomerService, which
 * targets the canonical /api/provider/customers CRUD surface.
 */
class ClientAccountService
{
    public function __construct(private readonly WaqtyApiClient $api) {}

    /**
     * @param  array<string, mixed>  $filters  search|branch_uuid|per_page|page
     * @return array<int, ClientAccountData>
     */
    public function clients(array $filters = []): array
    {
        return ClientAccountData::collect($this->rows($this->api->get('/api/provider/clients', $filters)));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, BookingData>
     */
    public function clientBookings(string $uuid, array $filters = []): array
    {
        return BookingData::collect($this->rows($this->api->get("/api/provider/clients/{$uuid}/bookings", $filters)));
    }

    /**
     * @param  array<string, mixed>  $filters  search|branch_uuid|per_page
     * @return array<int, ClientStatementRowData>
     */
    public function statements(array $filters = []): array
    {
        return ClientStatementRowData::collect($this->rows($this->api->get('/api/provider/clients/statements', $filters)));
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
