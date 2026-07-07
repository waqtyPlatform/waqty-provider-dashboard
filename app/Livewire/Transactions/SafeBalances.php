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
#[Title('Safe Balances — Waqty')]
class SafeBalances extends Component
{
    /** @var array<int, array<string, mixed>>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    /** @return array<int, array<string, mixed>> */
    private function source(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $this->loaded = app(FinanceService::class)->safeBalances(['per_page' => 100]);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = $this->fallbackData();
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
    public function safes(): array
    {
        return $this->source();
    }

    /** @return array{total:int, count:int} */
    #[Computed]
    public function kpis(): array
    {
        $all = $this->source();
        $total = array_sum(array_map(fn ($s) => (int) ($s['balance'] ?? 0), $all));

        return ['total' => (int) $total, 'count' => count($all)];
    }

    public function render()
    {
        return view('livewire.transactions.safe-balances');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'SF1', 'name' => 'الخزنة الرئيسية', 'branch' => 'الفرع الرئيسي', 'balance' => 1250000, 'last_activity' => '2026-07-04 18:30:00', 'is_active' => true],
            ['uuid' => 'SF2', 'name' => 'خزنة الاستقبال', 'branch' => 'الفرع الرئيسي', 'balance' => 320000, 'last_activity' => '2026-07-04 14:05:00', 'is_active' => true],
            ['uuid' => 'SF3', 'name' => 'درج فرع المول', 'branch' => 'فرع المول', 'balance' => 85000, 'last_activity' => '2026-07-03 20:15:00', 'is_active' => true],
            ['uuid' => 'SF4', 'name' => 'خزنة المصروفات النثرية', 'branch' => 'فرع وسط البلد', 'balance' => 0, 'last_activity' => '2026-06-28 09:00:00', 'is_active' => false],
        ];
    }
}
