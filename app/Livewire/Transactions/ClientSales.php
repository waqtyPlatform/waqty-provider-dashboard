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
#[Title('Client Sales — Waqty')]
class ClientSales extends Component
{
    public string $search = '';

    public int $currentPage = 1;

    public int $perPage = 8;

    /** @var array<int, array<string, mixed>>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    public function updatedSearch(): void
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
            $rows = app(FinanceService::class)->clientSales(['per_page' => 100]);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $rows = $this->fallbackData();
        }

        $this->loaded = array_map(fn ($r) => $this->mapRow((array) $r), array_values($rows));

        return $this->loaded;
    }

    /**
     * Normalise a raw client-sales row into the shape the view renders.
     *
     * @param  array<string, mixed>  $r
     * @return array<string, mixed>
     */
    private function mapRow(array $r): array
    {
        return [
            'name' => (string) ($r['name'] ?? data_get($r, 'client.name') ?? ''),
            'group' => (string) ($r['group'] ?? 'regular'),
            'visits' => (int) ($r['visits'] ?? $r['visits_count'] ?? 0),
            'total' => (int) ($r['total'] ?? $r['total_spent'] ?? 0),
            'last_purchase' => $r['last_purchase'] ?? $r['last_purchase_at'] ?? null,
        ];
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

        if ($search === '') {
            return $this->source();
        }

        return array_values(array_filter(
            $this->source(),
            fn (array $r) => str_contains(mb_strtolower((string) $r['name']), $search),
        ));
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

    /** Largest single-client total, used to scale the proportional bar. */
    #[Computed]
    public function maxSpent(): int
    {
        $totals = array_map(fn (array $r) => $r['total'], $this->source());

        return $totals === [] ? 0 : max($totals);
    }

    /** @return array{revenue:int, clients:int, top:string} */
    #[Computed]
    public function kpis(): array
    {
        $all = $this->source();
        $revenue = array_sum(array_map(fn (array $r) => $r['total'], $all));

        $top = '';
        $max = -1;
        foreach ($all as $r) {
            if ($r['total'] > $max) {
                $max = $r['total'];
                $top = $r['name'];
            }
        }

        return ['revenue' => $revenue, 'clients' => count($all), 'top' => $top];
    }

    public function render()
    {
        return view('livewire.transactions.client-sales');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['name' => 'فاطمة رشاد', 'group' => 'vip', 'visits' => 14, 'total' => 482000, 'last_purchase' => '2026-07-03'],
            ['name' => 'منى عادل', 'group' => 'vip', 'visits' => 11, 'total' => 398000, 'last_purchase' => '2026-07-01'],
            ['name' => 'سارة أحمد', 'group' => 'regular', 'visits' => 8, 'total' => 265000, 'last_purchase' => '2026-07-02'],
            ['name' => 'هناء فتحي', 'group' => 'regular', 'visits' => 6, 'total' => 174000, 'last_purchase' => '2026-06-28'],
            ['name' => 'ليلى حسن', 'group' => 'new', 'visits' => 3, 'total' => 92000, 'last_purchase' => '2026-06-29'],
            ['name' => 'مريم خالد', 'group' => 'new', 'visits' => 2, 'total' => 56000, 'last_purchase' => '2026-06-25'],
        ];
    }
}
