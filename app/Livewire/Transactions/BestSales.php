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
#[Title('Best Sellers — Waqty')]
class BestSales extends Component
{
    /** Leaderboard grouping: 'service' | 'employee'. */
    public string $view = 'service';

    /** @var array<int, array{name:string, count:int, revenue:int}>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    public function setView(string $view): void
    {
        $this->view = in_array($view, ['service', 'employee'], true) ? $view : 'service';
        $this->loaded = null;
        $this->fallbackUsed = false;
    }

    /** @return array<int, array{name:string, count:int, revenue:int}> */
    private function source(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $rows = app(FinanceService::class)->bestSales(['group_by' => $this->view]);
            $this->loaded = $this->normalize($rows);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = $this->normalize($this->fallbackData());
        }

        return $this->loaded;
    }

    public function usingFallback(): bool
    {
        $this->source();

        return $this->fallbackUsed;
    }

    /** Ranked leaderboard, highest revenue first, capped at the top 10. */
    /** @return array<int, array{name:string, count:int, revenue:int}> */
    #[Computed]
    public function ranked(): array
    {
        $rows = $this->source();
        usort($rows, fn (array $a, array $b) => $b['revenue'] <=> $a['revenue']);

        return array_slice($rows, 0, 10);
    }

    /** Largest revenue in the ranked set — the 100% baseline for proportional bars. */
    #[Computed]
    public function maxRevenue(): int
    {
        $revenues = array_map(fn (array $r) => $r['revenue'], $this->ranked());

        return $revenues === [] ? 1 : max(1, max($revenues));
    }

    /** @return array{top:string, revenue:int, count:int} */
    #[Computed]
    public function kpis(): array
    {
        $rows = $this->ranked();

        return [
            'top' => $rows[0]['name'] ?? '—',
            'revenue' => array_sum(array_map(fn (array $r) => $r['revenue'], $rows)),
            'count' => array_sum(array_map(fn (array $r) => $r['count'], $rows)),
        ];
    }

    public function render()
    {
        return view('livewire.transactions.best-sales');
    }

    /**
     * Coerce raw API rows into the leaderboard shape used by the view.
     *
     * @param  array<int, mixed>  $rows
     * @return array<int, array{name:string, count:int, revenue:int}>
     */
    private function normalize(array $rows): array
    {
        return array_values(array_map(function ($r) {
            $r = (array) $r;

            return [
                'name' => (string) ($r['name'] ?? $r['service'] ?? $r['employee'] ?? '—'),
                'count' => (int) ($r['count'] ?? $r['quantity'] ?? $r['sold'] ?? 0),
                'revenue' => (int) ($r['revenue'] ?? $r['total'] ?? $r['amount'] ?? 0),
            ];
        }, $rows));
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        if ($this->view === 'employee') {
            return [
                ['name' => 'سارة أحمد', 'count' => 180, 'revenue' => 990000],
                ['name' => 'ياسمين فاروق', 'count' => 145, 'revenue' => 812000],
                ['name' => 'منى عادل', 'count' => 132, 'revenue' => 726000],
                ['name' => 'خالد حسن', 'count' => 165, 'revenue' => 660000],
                ['name' => 'طارق سامي', 'count' => 110, 'revenue' => 495000],
                ['name' => 'هبة كمال', 'count' => 96, 'revenue' => 432000],
                ['name' => 'نور الدين', 'count' => 78, 'revenue' => 351000],
            ];
        }

        return [
            ['name' => 'صبغة شعر', 'count' => 142, 'revenue' => 852000],
            ['name' => 'قص شعر', 'count' => 210, 'revenue' => 630000],
            ['name' => 'حمام كريم', 'count' => 98, 'revenue' => 441000],
            ['name' => 'مكياج', 'count' => 64, 'revenue' => 384000],
            ['name' => 'مانيكير', 'count' => 120, 'revenue' => 300000],
            ['name' => 'تنظيف بشرة', 'count' => 55, 'revenue' => 275000],
            ['name' => 'باديكير', 'count' => 88, 'revenue' => 220000],
        ];
    }
}
