<?php

declare(strict_types=1);

namespace App\Livewire\Transactions;

use App\Services\Waqty\FinanceService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Petty Cash — Waqty')]
class PettyCash extends Component
{
    use HandlesWaqtyErrors;

    public string $search = '';

    public string $statusFilter = 'all';

    public int $currentPage = 1;

    public int $perPage = 8;

    // New petty-cash slide-over
    public bool $showForm = false;

    public string $form_category = '';

    public string $form_amount = '';

    public string $form_description = '';

    public string $form_approver = '';

    // Reject confirm-dialog
    public bool $showReject = false;

    public ?string $rejectingUuid = null;

    public string $rejectReason = '';

    /** Optimistic status override after approve/reject. @var array<string, string> */
    public array $overrides = [];

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
        if ($this->loaded === null) {
            try {
                $this->loaded = app(FinanceService::class)->pettyCash(['per_page' => 100]);
            } catch (WaqtyApiException) {
                $this->fallbackUsed = true;
                $this->loaded = $this->fallbackData();
            }
        }

        return array_map(function (array $row) {
            if (isset($row['uuid'], $this->overrides[$row['uuid']])) {
                $row['status'] = $this->overrides[$row['uuid']];
            }

            return $row;
        }, $this->loaded);
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
                || str_contains(mb_strtolower((string) ($r['reference'] ?? '')), $search)
                || str_contains(mb_strtolower((string) ($r['description'] ?? '')), $search)
                || str_contains(mb_strtolower((string) ($r['requested_by'] ?? '')), $search);

            return $matchesSearch && ($status === 'all' || ($r['status'] ?? '') === $status);
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

    /** @return array{spent:int, pending:int, thisMonth:int} */
    #[Computed]
    public function kpis(): array
    {
        $all = $this->source();
        $month = Carbon::now()->format('Y-m');

        return [
            'spent' => array_sum(array_map(
                fn (array $r) => ($r['status'] ?? '') === 'approved' ? (int) ($r['amount'] ?? 0) : 0,
                $all
            )),
            'pending' => count(array_filter($all, fn (array $r) => ($r['status'] ?? '') === 'pending')),
            'thisMonth' => array_sum(array_map(
                fn (array $r) => str_starts_with((string) ($r['date'] ?? ''), $month) ? (int) ($r['amount'] ?? 0) : 0,
                $all
            )),
        ];
    }

    /** @return array<int, string> */
    public function categories(): array
    {
        return ['ضيافة', 'مواصلات', 'صيانة', 'مستلزمات مكتبية', 'نظافة', 'أخرى'];
    }

    public function openCreate(): void
    {
        $this->reset(['form_category', 'form_amount', 'form_description', 'form_approver']);
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_category' => ['required', 'string', 'max:100'],
            'form_amount' => ['required', 'numeric', 'min:0.01'],
            'form_description' => ['required', 'string', 'max:300'],
            'form_approver' => ['nullable', 'string', 'max:100'],
        ], [
            'form_category.required' => __('txn.pettycash.categoryRequired'),
            'form_amount.required' => __('txn.pettycash.amountRequired'),
            'form_amount.min' => __('txn.pettycash.amountPositive'),
            'form_description.required' => __('txn.pettycash.descRequired'),
        ]);

        $payload = [
            'category' => trim($this->form_category),
            'amount' => Money::toMinor((float) $this->form_amount),
            'description' => trim($this->form_description),
            'approver' => trim($this->form_approver) ?: null,
        ];

        $result = $this->waqty(fn () => app(FinanceService::class)->createPettyCash($payload), __('waqty.genericError'));

        if ($result || $this->usingFallback()) {
            $this->showForm = false;
            $this->loaded = null;
            unset($this->filtered, $this->paginated, $this->total, $this->kpis);
            $this->dispatch('notify', type: 'success', message: __('txn.pettycash.toastAdded'));
        }
    }

    public function approve(string $uuid): void
    {
        $this->overrides[$uuid] = 'approved';

        if (! $this->usingFallback()) {
            $this->waqty(fn () => app(FinanceService::class)->approvePettyCash($uuid) ?? true, __('waqty.genericError'));
        }

        unset($this->filtered, $this->paginated, $this->total, $this->kpis);
        $this->dispatch('notify', type: 'success', message: __('common.saved'));
    }

    public function openReject(string $uuid): void
    {
        $this->rejectingUuid = $uuid;
        $this->rejectReason = '';
        $this->resetValidation();
        $this->showReject = true;
    }

    public function submitReject(): void
    {
        $this->validate(['rejectReason' => ['required', 'string', 'max:300']], [
            'rejectReason.required' => __('txn.pettycash.reasonRequired'),
        ]);

        $uuid = (string) $this->rejectingUuid;
        $reason = trim($this->rejectReason);
        $this->overrides[$uuid] = 'rejected';

        if (! $this->usingFallback()) {
            $this->waqty(fn () => app(FinanceService::class)->rejectPettyCash($uuid, $reason) ?? true, __('waqty.genericError'));
        }

        $this->showReject = false;
        $this->rejectingUuid = null;
        unset($this->filtered, $this->paginated, $this->total, $this->kpis);
        $this->dispatch('notify', type: 'success', message: __('common.saved'));
    }

    public function render()
    {
        return view('livewire.transactions.petty-cash');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'PC1', 'reference' => 'PC-100501', 'category' => 'ضيافة', 'description' => 'قهوة وضيافة للعملاء', 'amount' => 12000, 'requested_by' => 'سارة أحمد', 'status' => 'pending', 'date' => '2026-07-04'],
            ['uuid' => 'PC2', 'reference' => 'PC-100502', 'category' => 'مواصلات', 'description' => 'أجرة توصيل مستلزمات', 'amount' => 8000, 'requested_by' => 'منى عادل', 'status' => 'approved', 'date' => '2026-07-03'],
            ['uuid' => 'PC3', 'reference' => 'PC-100503', 'category' => 'صيانة', 'description' => 'إصلاح مجفف الشعر', 'amount' => 25000, 'requested_by' => 'خالد حسن', 'status' => 'pending', 'date' => '2026-07-02'],
            ['uuid' => 'PC4', 'reference' => 'PC-100504', 'category' => 'مستلزمات مكتبية', 'description' => 'أوراق وأقلام للاستقبال', 'amount' => 5000, 'requested_by' => 'ياسمين فاروق', 'status' => 'approved', 'date' => '2026-06-28'],
            ['uuid' => 'PC5', 'reference' => 'PC-100505', 'category' => 'نظافة', 'description' => 'مواد تنظيف الصالون', 'amount' => 15000, 'requested_by' => 'طارق سامي', 'status' => 'rejected', 'date' => '2026-06-25'],
        ];
    }
}
