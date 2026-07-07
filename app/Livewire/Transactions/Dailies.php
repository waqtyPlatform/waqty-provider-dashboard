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
#[Title('Dailies — Waqty')]
class Dailies extends Component
{
    public int $currentPage = 1;

    public int $perPage = 10;

    /** @var array<int, array<string, int|string>>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    /** @return array<int, array<string, int|string>> */
    private function source(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $rows = app(FinanceService::class)->dailies(['per_page' => 100]);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $rows = $this->fallbackData();
        }

        $rows = array_map(fn ($r) => $this->normalize(is_array($r) ? $r : []), $rows);
        usort($rows, fn ($a, $b) => strcmp((string) $b['date'], (string) $a['date']));

        return $this->loaded = $rows;
    }

    public function usingFallback(): bool
    {
        $this->source();

        return $this->fallbackUsed;
    }

    /** @return array<int, array<string, int|string>> */
    #[Computed]
    public function paginated(): array
    {
        return array_slice($this->source(), ($this->currentPage - 1) * $this->perPage, $this->perPage);
    }

    #[Computed]
    public function total(): int
    {
        return count($this->source());
    }

    /** @return array{sales:int, net:int, bestDate:?string, bestNet:int} */
    #[Computed]
    public function kpis(): array
    {
        $all = $this->source();
        $sales = array_sum(array_map(fn ($r) => (int) $r['sales'], $all));
        $net = array_sum(array_map(fn ($r) => (int) $r['net'], $all));

        $bestDate = null;
        $bestNet = 0;
        foreach ($all as $r) {
            if ($bestDate === null || (int) $r['net'] > $bestNet) {
                $bestDate = (string) $r['date'];
                $bestNet = (int) $r['net'];
            }
        }

        return ['sales' => $sales, 'net' => $net, 'bestDate' => $bestDate, 'bestNet' => $bestNet];
    }

    public function render()
    {
        return view('livewire.transactions.dailies');
    }

    /**
     * @param  array<string, mixed>  $r
     * @return array<string, int|string>
     */
    private function normalize(array $r): array
    {
        $sales = (int) ($r['sales'] ?? 0);
        $refunds = (int) ($r['refunds'] ?? 0);
        $expenses = (int) ($r['expenses'] ?? 0);

        return [
            'date' => (string) ($r['date'] ?? ''),
            'sales' => $sales,
            'refunds' => $refunds,
            'expenses' => $expenses,
            'net' => isset($r['net']) ? (int) $r['net'] : $sales - $refunds - $expenses,
        ];
    }

    /** @return array<int, array<string, int|string>> */
    private function fallbackData(): array
    {
        return [
            ['date' => '2026-07-04', 'sales' => 452000, 'refunds' => 18000, 'expenses' => 63000],
            ['date' => '2026-07-03', 'sales' => 388000, 'refunds' => 12000, 'expenses' => 55000],
            ['date' => '2026-07-02', 'sales' => 296000, 'refunds' => 24000, 'expenses' => 41000],
            ['date' => '2026-07-01', 'sales' => 512000, 'refunds' => 9000, 'expenses' => 72000],
            ['date' => '2026-06-30', 'sales' => 274000, 'refunds' => 15000, 'expenses' => 38000],
            ['date' => '2026-06-29', 'sales' => 331000, 'refunds' => 21000, 'expenses' => 47000],
            ['date' => '2026-06-28', 'sales' => 205000, 'refunds' => 6000, 'expenses' => 33000],
            ['date' => '2026-06-27', 'sales' => 358000, 'refunds' => 0, 'expenses' => 52000],
        ];
    }
}
