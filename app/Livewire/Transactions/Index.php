<?php

declare(strict_types=1);

namespace App\Livewire\Transactions;

use App\Data\Waqty\TransactionData;
use App\Services\Waqty\TransactionService;
use App\Services\Waqty\WaqtyApiException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Transactions — Waqty')]
class Index extends Component
{
    public string $search = '';

    public string $typeFilter = 'all';

    public int $currentPage = 1;

    public int $perPage = 8;

    /** @var array<int, TransactionData>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    public function updatedSearch(): void
    {
        $this->currentPage = 1;
    }

    public function updatedTypeFilter(): void
    {
        $this->currentPage = 1;
    }

    /** @return array<int, TransactionData> */
    private function source(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $this->loaded = app(TransactionService::class)->transactions(['per_page' => 100]);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = array_map(fn ($a) => TransactionData::from($a), $this->fallbackData());
        }

        return $this->loaded;
    }

    public function usingFallback(): bool
    {
        $this->source();

        return $this->fallbackUsed;
    }

    /** @return array<int, TransactionData> */
    #[Computed]
    public function filtered(): array
    {
        $search = trim(mb_strtolower($this->search));
        $type = $this->typeFilter;

        return array_values(array_filter($this->source(), function (TransactionData $t) use ($search, $type) {
            $matchesSearch = $search === ''
                || str_contains(mb_strtolower((string) $t->reference_number), $search)
                || str_contains(mb_strtolower((string) $t->customerName()), $search);

            return ($type === 'all' || $t->type === $type) && $matchesSearch;
        }));
    }

    /** @return array<int, TransactionData> */
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

    /** @return array{sales:int, refunds:int, net:int} */
    #[Computed]
    public function kpis(): array
    {
        $all = $this->source();
        $sales = array_sum(array_map(fn (TransactionData $t) => $t->type === 'sale' ? $t->amount : 0, $all));
        $refunds = array_sum(array_map(fn (TransactionData $t) => $t->isRefund() ? $t->amount : 0, $all));

        return ['sales' => $sales, 'refunds' => $refunds, 'net' => $sales - $refunds];
    }

    public function render()
    {
        return view('livewire.transactions.index');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'T1', 'type' => 'sale', 'amount' => 45000, 'payment_method' => 'card', 'status' => 'completed', 'customer' => ['name' => 'ليلى حسن'], 'employee' => ['name' => 'د. سارة أحمد'], 'reference_number' => 'TXN-100234', 'created_at' => '2026-07-03 15:20:00'],
            ['uuid' => 'T2', 'type' => 'sale', 'amount' => 15000, 'payment_method' => 'cash', 'status' => 'completed', 'customer' => ['name' => 'عمر خالد'], 'employee' => ['name' => 'خالد حسن'], 'reference_number' => 'TXN-100235', 'created_at' => '2026-07-03 12:10:00'],
            ['uuid' => 'T3', 'type' => 'refund', 'amount' => 15000, 'payment_method' => 'cash', 'status' => 'completed', 'customer' => ['name' => 'يوسف علي'], 'employee' => ['name' => 'الاستقبال'], 'reference_number' => 'TXN-100236', 'created_at' => '2026-07-02 18:40:00'],
            ['uuid' => 'T4', 'type' => 'advance_payment', 'amount' => 50000, 'payment_method' => 'card', 'status' => 'completed', 'customer' => ['name' => 'مريم عادل'], 'employee' => ['name' => 'منى عادل'], 'reference_number' => 'TXN-100237', 'created_at' => '2026-07-02 10:05:00'],
            ['uuid' => 'T5', 'type' => 'petty_cash', 'amount' => 8000, 'payment_method' => 'cash', 'status' => 'completed', 'customer' => null, 'employee' => ['name' => 'منى عادل'], 'reference_number' => 'TXN-100238', 'created_at' => '2026-07-01 09:30:00'],
            ['uuid' => 'T6', 'type' => 'transfer', 'amount' => 100000, 'payment_method' => 'bank', 'status' => 'pending', 'customer' => null, 'employee' => ['name' => 'د. سارة أحمد'], 'reference_number' => 'TXN-100239', 'created_at' => '2026-07-01 08:00:00'],
            ['uuid' => 'T7', 'type' => 'sale', 'amount' => 55000, 'payment_method' => 'card', 'status' => 'completed', 'customer' => ['name' => 'هناء فتحي'], 'employee' => ['name' => 'ياسمين فاروق'], 'reference_number' => 'TXN-100240', 'created_at' => '2026-06-30 16:15:00'],
            ['uuid' => 'T8', 'type' => 'sale', 'amount' => 30000, 'payment_method' => 'cash', 'status' => 'completed', 'customer' => ['name' => 'سلمى إبراهيم'], 'employee' => ['name' => 'خالد حسن'], 'reference_number' => 'TXN-100241', 'created_at' => '2026-06-30 11:00:00'],
            ['uuid' => 'T9', 'type' => 'sale', 'amount' => 20000, 'payment_method' => 'card', 'status' => 'partial', 'customer' => ['name' => 'كريم مصطفى'], 'employee' => ['name' => 'طارق سامي'], 'reference_number' => 'TXN-100242', 'created_at' => '2026-06-29 14:20:00'],
        ];
    }
}
