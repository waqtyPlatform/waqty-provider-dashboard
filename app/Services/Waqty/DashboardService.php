<?php

declare(strict_types=1);

namespace App\Services\Waqty;

use App\Data\Waqty\DashboardData;
use App\Data\Waqty\DashboardSummaryData;

/**
 * Port of `providerApi.getDashboard` + `dashboardApi.getSummary`
 * (src/lib/api.ts). Endpoints /api/provider/dashboard[/summary].
 */
class DashboardService
{
    public function __construct(private readonly WaqtyApiClient $api) {}

    public function dashboard(): DashboardData
    {
        return DashboardData::from($this->api->get('/api/provider/dashboard') ?? []);
    }

    /** @param array<string, mixed> $filters */
    public function summary(array $filters = []): DashboardSummaryData
    {
        return DashboardSummaryData::from($this->api->get('/api/provider/dashboard/summary', $filters) ?? []);
    }

    /** @return array<string, mixed>|null the next upcoming booking (raw shape) */
    public function nextUpcoming(): ?array
    {
        $data = $this->api->get('/api/provider/bookings/next-upcoming');

        return is_array($data) ? $data : null;
    }
}
