<?php

declare(strict_types=1);

namespace App\Services\Waqty;

use App\Data\Waqty\ProviderRevenueData;
use App\Data\Waqty\ReportSeriesData;

/**
 * Port of `reportApi` + `providerApi.getRevenue` (src/lib/api.ts).
 * Reports return {labels, datasets, summary}; /revenue returns the aggregated
 * ProviderRevenue (total + by_branch + by_employee).
 */
class ReportService
{
    public function __construct(private readonly WaqtyApiClient $api) {}

    /** @param array<string, mixed> $filters from_date|to_date|branch_uuid|group_by */
    public function report(string $type, array $filters = []): ReportSeriesData
    {
        return ReportSeriesData::from($this->api->get("/api/provider/reports/{$type}", $filters) ?? []);
    }

    /** @param array<string, mixed> $filters */
    public function revenueReport(array $filters = []): ReportSeriesData
    {
        return $this->report('revenue', $filters);
    }

    /** @param array<string, mixed> $filters branch_uuid|employee_uuid|start_date|end_date */
    public function revenue(array $filters = []): ProviderRevenueData
    {
        return ProviderRevenueData::from($this->api->get('/api/provider/revenue', $filters) ?? []);
    }

    /**
     * Tabular rows for a specific drill-down report (e.g. /reports/revenue/by-branch).
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, mixed>
     */
    public function reportTable(string $category, string $report, array $filters = []): array
    {
        $data = $this->api->get("/api/provider/reports/{$category}/{$report}", $filters);

        if (is_array($data) && isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }

        return is_array($data) ? $data : [];
    }

    /**
     * Request an export; returns {url} for the generated CSV/PDF (mock when unwired).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function export(string $category, string $report, array $filters = []): array
    {
        $data = $this->api->post("/api/provider/reports/{$category}/{$report}/export", $filters);

        return is_array($data) ? $data : [];
    }
}
