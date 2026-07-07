<?php

declare(strict_types=1);

namespace App\Livewire\Transactions;

use App\Services\Waqty\FinanceService;
use App\Services\Waqty\WaqtyApiException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Package Sales — Waqty')]
class PackageSales extends Component
{
    public string $search = '';

    public string $statusFilter = 'all';

    public int $currentPage = 1;

    public int $perPage = 8;

    /** @var array<int, array<string, mixed>>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    public function updatedSearch(): void
    {
        $this->currentPage = 1;
    }

    public function updatedStatusFilter(): void
    {
        $this->currentPage = 1;
    }

    /** @return array<int, array<string, mixed>> */
    private function source(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $rows = app(FinanceService::class)->packageSales(['per_page' => 100]);
            $this->loaded = array_map(fn ($r) => $this->normalize($r), $rows);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = array_map(fn ($r) => $this->normalize($r), $this->fallbackData());
        }

        return $this->loaded;
    }

    public function usingFallback(): bool
    {
        $this->source();

        return $this->fallbackUsed;
    }

    /** @return array<int, array<string, mixed>> */
    #[Computed]
    public function filtered(): array
    {
        $search = trim(mb_strtolower($this->search));
        $status = $this->statusFilter;

        return array_values(array_filter($this->source(), function (array $r) use ($search, $status) {
            $matchesSearch = $search === ''
                || str_contains(mb_strtolower((string) $r['package']), $search)
                || str_contains(mb_strtolower((string) $r['client']), $search);

            return ($status === 'all' || $r['status'] === $status) && $matchesSearch;
        }));
    }

    /** @return array<int, array<string, mixed>> */
    #[Computed]
    public function paginated(): array
    {
        return array_slice($this->filtered(), ($this->currentPage - 1) * $this->perPage, $this->perPage);
    }

    #[Computed]
    public function total(): int
    {
        return count($this->filtered());
    }

    /** @return array{active:int, sessions:int, revenue:int} */
    #[Computed]
    public function kpis(): array
    {
        $all = $this->source();

        return [
            'active' => count(array_filter($all, fn (array $r) => $r['status'] === 'active')),
            'sessions' => (int) array_sum(array_map(fn (array $r) => $r['used'], $all)),
            'revenue' => (int) array_sum(array_map(fn (array $r) => $r['amount'], $all)),
        ];
    }

    public function render()
    {
        return view('livewire.transactions.package-sales');
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalize(array $row): array
    {
        $client = $row['client'] ?? null;

        return [
            'uuid' => (string) ($row['uuid'] ?? ''),
            'package' => (string) ($row['package_name'] ?? $row['package'] ?? ''),
            'client' => is_array($client) ? (string) ($client['name'] ?? '') : (string) ($client ?? ''),
            'used' => (int) ($row['sessions_used'] ?? 0),
            'total' => (int) ($row['sessions_total'] ?? 0),
            'amount' => (int) ($row['amount'] ?? $row['amount_paid'] ?? 0),
            'soldAt' => (string) ($row['sold_at'] ?? $row['date'] ?? ''),
            'status' => (string) ($row['status'] ?? 'active'),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'PKG-1', 'package_name' => 'باقة العناية بالبشرة', 'client' => ['name' => 'فاطمة رشاد'], 'sessions_used' => 3, 'sessions_total' => 10, 'amount' => 150000, 'sold_at' => '2026-06-20', 'status' => 'active'],
            ['uuid' => 'PKG-2', 'package_name' => 'باقة الليزر الكامل', 'client' => ['name' => 'سارة أحمد'], 'sessions_used' => 8, 'sessions_total' => 8, 'amount' => 480000, 'sold_at' => '2026-05-11', 'status' => 'completed'],
            ['uuid' => 'PKG-3', 'package_name' => 'باقة قص وصبغة الشعر', 'client' => ['name' => 'منى عادل'], 'sessions_used' => 2, 'sessions_total' => 6, 'amount' => 90000, 'sold_at' => '2026-06-28', 'status' => 'active'],
            ['uuid' => 'PKG-4', 'package_name' => 'باقة المساج العلاجي', 'client' => ['name' => 'ليلى حسن'], 'sessions_used' => 5, 'sessions_total' => 12, 'amount' => 264000, 'sold_at' => '2026-06-15', 'status' => 'active'],
            ['uuid' => 'PKG-5', 'package_name' => 'باقة تنظيف البشرة العميق', 'client' => ['name' => 'هناء فتحي'], 'sessions_used' => 4, 'sessions_total' => 4, 'amount' => 120000, 'sold_at' => '2026-04-30', 'status' => 'completed'],
            ['uuid' => 'PKG-6', 'package_name' => 'باقة العروس الكاملة', 'client' => ['name' => 'مريم عادل'], 'sessions_used' => 1, 'sessions_total' => 15, 'amount' => 900000, 'sold_at' => '2026-07-01', 'status' => 'active'],
        ];
    }
}
