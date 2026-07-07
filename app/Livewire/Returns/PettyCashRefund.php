<?php

declare(strict_types=1);

namespace App\Livewire\Returns;

use App\Services\Waqty\ReturnService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Petty-cash refund wizard (/returns/petty-cash-refund):
 * 1 pick a petty-cash entry -> 2 reason + notes -> 3 confirm & submit.
 *
 * Models the onboarding wizard: an int $step, a segmented progress bar,
 * per-step validation on next(). Like the rest of the Returns cluster it is a
 * UI clone with no backend, so a submit that hits a {@see WaqtyApiException}
 * still lands on the success state (demo/fallback parity).
 */
#[Layout('components.layouts.app')]
#[Title('Petty cash refund — Waqty')]
class PettyCashRefund extends Component
{
    use HandlesWaqtyErrors;

    public int $step = 1;

    /** Selected petty-cash entry uuid (step 1). */
    public ?string $pettyCashUuid = null;

    /** Refund reason (step 2). */
    public string $reason = '';

    /** Optional free-text notes (step 2). */
    public string $notes = '';

    /** Success panel flag (after submit). */
    public bool $done = false;

    public function selectEntry(string $uuid): void
    {
        if ($this->entry($uuid) !== null) {
            $this->pettyCashUuid = $uuid;
            $this->resetValidation('pettyCashUuid');
        }
    }

    public function next(): void
    {
        $this->validateStep($this->step);

        if ($this->step < 3) {
            $this->step++;
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
        // Re-guard the earlier steps before the final write.
        $this->validateStep(1);
        $this->validateStep(2);

        $entry = $this->selectedEntry();
        if ($entry === null) {
            $this->step = 1;

            return;
        }

        try {
            app(ReturnService::class)->create([
                'type' => 'petty_cash_refund',
                'petty_cash_uuid' => $this->pettyCashUuid,
                'amount' => $entry['amount'],
                'reason' => trim($this->reason),
                'notes' => trim($this->notes),
            ]);
        } catch (WaqtyApiException) {
            // No backend in this clone — complete the flow anyway (fallback).
        }

        $this->done = true;
        $this->dispatch('notify', type: 'success', message: __('returns.pettyCashRefund.successNotify'));
    }

    public function startAnother(): void
    {
        $this->reset(['step', 'pettyCashUuid', 'reason', 'notes', 'done']);
        $this->resetValidation();
    }

    /** Validate the fields owned by a given step. */
    protected function validateStep(int $step): void
    {
        if ($step === 1) {
            $this->validate([
                'pettyCashUuid' => ['required', 'string'],
            ], [
                'pettyCashUuid.required' => __('returns.pettyCashRefund.selectEntryRequired'),
            ]);

            if ($this->selectedEntry() === null) {
                $this->addError('pettyCashUuid', __('returns.pettyCashRefund.selectEntryRequired'));
            }

            return;
        }

        if ($step === 2) {
            $this->validate([
                'reason' => ['required', 'string', 'max:200'],
                'notes' => ['nullable', 'string', 'max:500'],
            ], [
                'reason.required' => __('returns.pettyCashRefund.reasonRequired'),
            ]);
        }
    }

    /** The currently selected entry, or null. @return array<string, mixed>|null */
    public function selectedEntry(): ?array
    {
        return $this->pettyCashUuid ? $this->entry($this->pettyCashUuid) : null;
    }

    /** Look up a sample entry by uuid. @return array<string, mixed>|null */
    private function entry(string $uuid): ?array
    {
        foreach ($this->entries() as $e) {
            if ($e['uuid'] === $uuid) {
                return $e;
            }
        }

        return null;
    }

    /**
     * Selectable reasons for a petty-cash refund (localised copy).
     *
     * @return array<int, string>
     */
    public function reasonOptions(): array
    {
        return [
            __('returns.pettyCashRefund.reasonUnusedSupplies'),
            __('returns.pettyCashRefund.reasonOverpaid'),
            __('returns.pettyCashRefund.reasonWrongItem'),
            __('returns.pettyCashRefund.reasonDuplicate'),
            __('returns.pettyCashRefund.reasonCancelledPurchase'),
            __('returns.pettyCashRefund.reasonOther'),
        ];
    }

    /**
     * Arabic sample petty-cash entries to pick from (UI clone — no backend).
     * amount is in integer minor units (100 = 1 EGP).
     *
     * @return array<int, array<string, mixed>>
     */
    public function entries(): array
    {
        return [
            ['uuid' => 'PC1', 'category' => 'مستلزمات نظافة', 'description' => 'شراء منظفات ومناديل ورقية للفرع', 'amount' => 45000, 'date' => '2026-07-03', 'employee' => 'فاطمة رشاد'],
            ['uuid' => 'PC2', 'category' => 'ضيافة', 'description' => 'قهوة وشاي وعصائر لغرفة انتظار العملاء', 'amount' => 18000, 'date' => '2026-07-02', 'employee' => 'مروان سعيد'],
            ['uuid' => 'PC3', 'category' => 'صيانة', 'description' => 'إصلاح مجفف الشعر بقسم التصفيف', 'amount' => 60000, 'date' => '2026-07-01', 'employee' => 'نهى جمال'],
            ['uuid' => 'PC4', 'category' => 'مواصلات', 'description' => 'أجرة توصيل مستلزمات من المورد', 'amount' => 12000, 'date' => '2026-06-30', 'employee' => 'كريم عادل'],
            ['uuid' => 'PC5', 'category' => 'أدوات مكتبية', 'description' => 'دفاتر مواعيد وأقلام لمكتب الاستقبال', 'amount' => 22000, 'date' => '2026-06-28', 'employee' => 'فاطمة رشاد'],
        ];
    }

    public function render()
    {
        return view('livewire.returns.petty-cash-refund');
    }
}
