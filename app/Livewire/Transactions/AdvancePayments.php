<?php

declare(strict_types=1);

namespace App\Livewire\Transactions;

use App\Services\Waqty\FinanceService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use App\Support\Money;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Advance Payments — Waqty')]
class AdvancePayments extends Component
{
    use HandlesWaqtyErrors;

    public string $search = '';

    public int $currentPage = 1;

    public int $perPage = 8;

    // New advance slide-over
    public bool $showForm = false;

    public string $form_client = '';

    public string $form_amount = '';

    public string $form_method = 'cash';

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
            $this->loaded = app(FinanceService::class)->advancePayments(['per_page' => 100]);
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
    public function filtered(): array
    {
        $search = trim(mb_strtolower($this->search));

        return array_values(array_filter($this->source(), function (array $r) use ($search) {
            return $search === ''
                || str_contains(mb_strtolower((string) ($r['reference'] ?? '')), $search)
                || str_contains(mb_strtolower((string) ($r['client'] ?? '')), $search);
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

    /** @return array{total:int, outstanding:int, count:int} */
    #[Computed]
    public function kpis(): array
    {
        $all = $this->source();
        $total = array_sum(array_map(fn (array $r) => (int) ($r['amount'] ?? 0), $all));
        $outstanding = array_sum(array_map(
            fn (array $r) => empty($r['applied_to']) ? (int) ($r['amount'] ?? 0) : 0,
            $all
        ));

        return ['total' => $total, 'outstanding' => $outstanding, 'count' => count($all)];
    }

    public function openCreate(): void
    {
        $this->reset(['form_client', 'form_amount']);
        $this->form_method = 'cash';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_client' => ['required', 'string', 'max:120'],
            'form_amount' => ['required', 'numeric', 'min:0.01'],
            'form_method' => ['required', 'in:cash,card,bank'],
        ], [
            'form_client.required' => __('txn.advancepayments.clientRequired'),
            'form_amount.required' => __('txn.advancepayments.amountRequired'),
            'form_amount.min' => __('txn.advancepayments.amountRequired'),
        ]);

        $payload = [
            'client' => trim($this->form_client),
            'amount' => Money::toMinor((float) $this->form_amount),
            'payment_method' => $this->form_method,
        ];

        $result = $this->waqty(
            fn () => app(FinanceService::class)->createAdvancePayment($payload),
            __('waqty.genericError'),
        );

        if ($result || $this->usingFallback()) {
            $this->showForm = false;
            $this->loaded = null;
            unset($this->filtered, $this->paginated, $this->total, $this->kpis);
            $this->dispatch('notify', type: 'success', message: __('txn.advancepayments.toastAdded'));
        }
    }

    public function render()
    {
        return view('livewire.transactions.advance-payments');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'A1', 'reference' => 'ADV-100234', 'client' => 'فاطمة رشاد', 'amount' => 32000, 'date' => '2026-07-04', 'status' => 'completed', 'applied_to' => 'BKG-5521'],
            ['uuid' => 'A2', 'reference' => 'ADV-100235', 'client' => 'ليلى حسن', 'amount' => 50000, 'date' => '2026-07-03', 'status' => 'pending', 'applied_to' => null],
            ['uuid' => 'A3', 'reference' => 'ADV-100236', 'client' => 'سارة أحمد', 'amount' => 15000, 'date' => '2026-07-02', 'status' => 'completed', 'applied_to' => 'BKG-5533'],
            ['uuid' => 'A4', 'reference' => 'ADV-100237', 'client' => 'مريم عادل', 'amount' => 75000, 'date' => '2026-07-01', 'status' => 'pending', 'applied_to' => null],
            ['uuid' => 'A5', 'reference' => 'ADV-100238', 'client' => 'هناء فتحي', 'amount' => 20000, 'date' => '2026-06-30', 'status' => 'partial', 'applied_to' => 'BKG-5540'],
            ['uuid' => 'A6', 'reference' => 'ADV-100239', 'client' => 'نور الدين محمود', 'amount' => 40000, 'date' => '2026-06-28', 'status' => 'completed', 'applied_to' => 'BKG-5548'],
        ];
    }
}
