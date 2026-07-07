<?php

declare(strict_types=1);

namespace App\Services\Waqty;

/**
 * Port of the finance/transactions read endpoints (src/lib/api.ts transactionApi.*)
 * — /api/provider/transactions/{cash-sales,client-sales,advance-payments,petty-cash,
 * transfers,safe-balances,shift-totals,dailies,best-sales,package-sales}.
 *
 * These are read-mostly reporting endpoints; screens fall back to local sample
 * data when the live API is unavailable (mirrors the source FALLBACK_* approach).
 * Rows are returned as raw associative arrays (each finance screen shapes its own
 * columns) rather than a shared DTO.
 */
class FinanceService
{
    public function __construct(private readonly WaqtyApiClient $api) {}

    /** @param array<string, mixed> $filters @return array<int, mixed> */
    public function cashSales(array $filters = []): array
    {
        return $this->rows($this->api->get('/api/provider/transactions/cash-sales', $filters));
    }

    /** @param array<string, mixed> $filters @return array<int, mixed> */
    public function clientSales(array $filters = []): array
    {
        return $this->rows($this->api->get('/api/provider/transactions/client-sales', $filters));
    }

    /** @param array<string, mixed> $filters @return array<int, mixed> */
    public function advancePayments(array $filters = []): array
    {
        return $this->rows($this->api->get('/api/provider/transactions/advance-payments', $filters));
    }

    /** @param array<string, mixed> $data */
    public function createAdvancePayment(array $data): mixed
    {
        return $this->api->post('/api/provider/transactions/advance-payments', $data);
    }

    /** @param array<string, mixed> $filters @return array<int, mixed> */
    public function pettyCash(array $filters = []): array
    {
        return $this->rows($this->api->get('/api/provider/transactions/petty-cash', $filters));
    }

    /** @param array<string, mixed> $data */
    public function createPettyCash(array $data): mixed
    {
        return $this->api->post('/api/provider/transactions/petty-cash', $data);
    }

    public function approvePettyCash(string $uuid): mixed
    {
        return $this->api->patch("/api/provider/transactions/petty-cash/{$uuid}/approve");
    }

    public function rejectPettyCash(string $uuid, string $reason = ''): mixed
    {
        return $this->api->patch("/api/provider/transactions/petty-cash/{$uuid}/reject", ['reason' => $reason]);
    }

    /** @param array<string, mixed> $filters @return array<int, mixed> */
    public function transfers(array $filters = []): array
    {
        return $this->rows($this->api->get('/api/provider/transactions/transfers', $filters));
    }

    /** @param array<string, mixed> $data */
    public function createTransfer(array $data): mixed
    {
        return $this->api->post('/api/provider/transactions/transfers', $data);
    }

    /** @param array<string, mixed> $filters @return array<int, mixed> */
    public function safeBalances(array $filters = []): array
    {
        return $this->rows($this->api->get('/api/provider/transactions/safe-balances', $filters));
    }

    /** @param array<string, mixed> $filters @return array<int, mixed> */
    public function shiftTotals(array $filters = []): array
    {
        return $this->rows($this->api->get('/api/provider/transactions/shift-totals', $filters));
    }

    public function closeShift(string $uuid): mixed
    {
        return $this->api->patch("/api/provider/transactions/shift-totals/{$uuid}/close");
    }

    /** @param array<string, mixed> $filters @return array<int, mixed> */
    public function dailies(array $filters = []): array
    {
        return $this->rows($this->api->get('/api/provider/transactions/dailies', $filters));
    }

    /** @param array<string, mixed> $filters @return array<int, mixed> */
    public function bestSales(array $filters = []): array
    {
        return $this->rows($this->api->get('/api/provider/transactions/best-sales', $filters));
    }

    /** @param array<string, mixed> $filters @return array<int, mixed> */
    public function packageSales(array $filters = []): array
    {
        return $this->rows($this->api->get('/api/provider/transactions/package-sales', $filters));
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
