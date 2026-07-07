<?php

declare(strict_types=1);

namespace App\Livewire\Returns;

use App\Services\Waqty\ReturnService;
use App\Support\Concerns\HandlesWaqtyErrors;
use App\Support\Money;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Cash-refund wizard (type: cash_refund) — three steps:
 * 1 pick a completed sale, 2 choose line items + editable refund amounts,
 * 3 reason + notes -> confirm -> ReturnService::create(). Mirrors the
 * onboarding wizard shape (int $step, next()/back(), per-step validation) and,
 * like Settings\Safes, still completes under an API outage so the demo stays
 * fully walkable. Money is integer minor units throughout.
 */
#[Layout('components.layouts.app')]
#[Title('Cash refund — Waqty')]
class CashRefund extends Component
{
    use HandlesWaqtyErrors;

    public int $step = 1;

    public bool $done = false;

    // Step 1 — selected sale
    public ?string $transactionUuid = null;

    // Step 2 — line items to refund + editable amounts (major EGP strings)
    /** @var array<int, string> */
    public array $selectedItems = [];

    /** @var array<string, string> */
    public array $itemAmounts = [];

    // Step 3 — reason + notes
    public string $reason = '';

    public string $notes = '';

    public function selectTransaction(string $uuid): void
    {
        $this->transactionUuid = $uuid;
        $this->resetValidation();

        // Preselect every line with its full amount as a sensible default.
        $this->selectedItems = [];
        $this->itemAmounts = [];

        foreach ($this->selectedTransaction()['items'] ?? [] as $item) {
            $this->selectedItems[] = $item['id'];
            $this->itemAmounts[$item['id']] = (string) Money::fromMinor($item['amount']);
        }
    }

    public function toggleItem(string $id): void
    {
        if (in_array($id, $this->selectedItems, true)) {
            $this->selectedItems = array_values(array_filter($this->selectedItems, fn ($s) => $s !== $id));
        } else {
            $this->selectedItems[] = $id;
        }
    }

    public function next(): void
    {
        if ($this->step === 1) {
            $this->validate(
                ['transactionUuid' => ['required']],
                ['transactionUuid.required' => __('cashRefund.errNoTransaction')],
            );
            $this->step = 2;

            return;
        }

        if ($this->step === 2) {
            $this->validateItems();
            $this->step = 3;
        }
    }

    public function back(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function submit(): void
    {
        $this->validate(
            ['reason' => ['required']],
            ['reason.required' => __('cashRefund.errNoReason')],
        );

        $items = array_map(fn ($item) => [
            'id' => $item['id'],
            'name' => $item['name'],
            'amount' => $this->itemRefundMinor($item['id']),
        ], $this->chosenItems());

        $payload = [
            'type' => 'cash_refund',
            'transaction_uuid' => $this->transactionUuid,
            'items' => $items,
            'amount' => $this->refundTotalMinor(),
            'reason' => $this->reason,
            'notes' => trim($this->notes),
        ];

        // 401/422 are surfaced by the trait; any other outage is swallowed
        // (returns null) so the wizard still lands on the success panel.
        $this->waqty(fn () => app(ReturnService::class)->create($payload), __('cashRefund.submitFailed'));

        $this->done = true;
        $this->dispatch('notify', type: 'success', message: __('cashRefund.submitted'));
    }

    public function startOver(): void
    {
        $this->reset();
    }

    /** The sale currently selected in step 1. @return array<string, mixed>|null */
    public function selectedTransaction(): ?array
    {
        foreach ($this->transactions() as $t) {
            if ($t['uuid'] === $this->transactionUuid) {
                return $t;
            }
        }

        return null;
    }

    /** Line items of the selected sale that are ticked for refund. @return array<int, array<string, mixed>> */
    public function chosenItems(): array
    {
        $txn = $this->selectedTransaction();
        if ($txn === null) {
            return [];
        }

        return array_values(array_filter(
            $txn['items'],
            fn ($item) => in_array($item['id'], $this->selectedItems, true),
        ));
    }

    /** Refund amount entered for a line, in integer minor units. */
    public function itemRefundMinor(string $id): int
    {
        return Money::toMinor((float) ($this->itemAmounts[$id] ?? 0));
    }

    /** Running refund total across the ticked lines, in minor units. */
    public function refundTotalMinor(): int
    {
        $total = 0;
        foreach ($this->chosenItems() as $item) {
            $total += $this->itemRefundMinor($item['id']);
        }

        return $total;
    }

    public function render()
    {
        return view('livewire.returns.cash-refund');
    }

    private function validateItems(): void
    {
        $chosen = $this->chosenItems();

        if ($chosen === []) {
            throw ValidationException::withMessages([
                'selectedItems' => __('cashRefund.errNoItems'),
            ]);
        }

        $errors = [];
        foreach ($chosen as $item) {
            $amount = $this->itemRefundMinor($item['id']);
            if ($amount <= 0 || $amount > $item['amount']) {
                $errors['itemAmounts.'.$item['id']] = __('cashRefund.errAmountRange');
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Arabic sample sales to pick from (UI-only clone — no backend read).
     * Amounts are integer minor units (100 = EGP 1).
     *
     * @return array<int, array<string, mixed>>
     */
    public function transactions(): array
    {
        return [
            [
                'uuid' => 'TXN-4821',
                'client' => 'فاطمة رشاد',
                'service' => 'صبغة شعر + قصّ وتصفيف',
                'amount' => 68000,
                'date' => '2026-07-03',
                'items' => [
                    ['id' => 'TXN-4821-1', 'name' => 'صبغة شعر', 'amount' => 45000],
                    ['id' => 'TXN-4821-2', 'name' => 'قصّ وتصفيف', 'amount' => 15000],
                    ['id' => 'TXN-4821-3', 'name' => 'حمام كريم', 'amount' => 8000],
                ],
            ],
            [
                'uuid' => 'TXN-4790',
                'client' => 'مريم سمير',
                'service' => 'مانيكير + باديكير',
                'amount' => 42000,
                'date' => '2026-07-02',
                'items' => [
                    ['id' => 'TXN-4790-1', 'name' => 'مانيكير جل', 'amount' => 22000],
                    ['id' => 'TXN-4790-2', 'name' => 'باديكير سبا', 'amount' => 20000],
                ],
            ],
            [
                'uuid' => 'TXN-4756',
                'client' => 'هبة عبداللطيف',
                'service' => 'عناية بالبشرة',
                'amount' => 95000,
                'date' => '2026-07-01',
                'items' => [
                    ['id' => 'TXN-4756-1', 'name' => 'تنظيف بشرة عميق', 'amount' => 55000],
                    ['id' => 'TXN-4756-2', 'name' => 'ماسك ترطيب', 'amount' => 25000],
                    ['id' => 'TXN-4756-3', 'name' => 'مساج للوجه', 'amount' => 15000],
                ],
            ],
            [
                'uuid' => 'TXN-4712',
                'client' => 'نورهان عادل',
                'service' => 'مكياج سهرة',
                'amount' => 120000,
                'date' => '2026-06-30',
                'items' => [
                    ['id' => 'TXN-4712-1', 'name' => 'مكياج سهرة', 'amount' => 90000],
                    ['id' => 'TXN-4712-2', 'name' => 'تركيب رموش', 'amount' => 30000],
                ],
            ],
        ];
    }
}
