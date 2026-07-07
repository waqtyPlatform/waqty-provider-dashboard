<?php

declare(strict_types=1);

namespace App\Livewire\Transactions;

use App\Services\Waqty\FinanceService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use App\Support\Money;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Transfers — Waqty')]
class Transfers extends Component
{
    use HandlesWaqtyErrors;

    public string $search = '';

    public string $statusFilter = 'all';

    public int $currentPage = 1;

    public int $perPage = 8;

    // New transfer slide-over
    public bool $showForm = false;

    public string $form_fromSafe = 'main';

    public string $form_toSafe = '';

    public string $form_amount = '';

    public string $form_note = '';

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

    /** @return array<string, string> slug => safe name */
    public function safes(): array
    {
        return [
            'main' => 'الخزنة الرئيسية',
            'reception' => 'خزنة الاستقبال',
            'branch2' => 'خزنة الفرع الثاني',
            'petty' => 'الخزنة النثرية',
            'bank' => 'الخزنة البنكية',
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function source(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $rows = app(FinanceService::class)->transfers(['per_page' => 100]);
            $this->loaded = array_map(fn ($r) => $this->normalize((array) $r), $rows);
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

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalize(array $row): array
    {
        return [
            'uuid' => (string) ($row['uuid'] ?? $row['id'] ?? $row['reference'] ?? uniqid('trf_')),
            'reference' => (string) ($row['reference'] ?? $row['reference_number'] ?? '—'),
            'from_safe' => $this->safeName($row['from_safe'] ?? $row['from'] ?? $row['source_safe'] ?? null),
            'to_safe' => $this->safeName($row['to_safe'] ?? $row['to'] ?? $row['destination_safe'] ?? null),
            'amount' => (int) ($row['amount'] ?? 0),
            'date' => $row['date'] ?? $row['created_at'] ?? null,
            'status' => (string) ($row['status'] ?? 'completed'),
        ];
    }

    private function safeName(mixed $value): string
    {
        if (is_array($value)) {
            return (string) ($value['name'] ?? '—');
        }

        return $value !== null && $value !== '' ? (string) $value : '—';
    }

    /** @return array<int, array<string, mixed>> */
    #[Computed]
    public function filtered(): array
    {
        $search = trim(mb_strtolower($this->search));
        $status = $this->statusFilter;

        return array_values(array_filter($this->source(), function (array $t) use ($search, $status) {
            $matchesSearch = $search === ''
                || str_contains(mb_strtolower((string) $t['reference']), $search)
                || str_contains(mb_strtolower((string) $t['from_safe']), $search)
                || str_contains(mb_strtolower((string) $t['to_safe']), $search);

            return $matchesSearch && ($status === 'all' || $t['status'] === $status);
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

    /** @return array{transferred:int, count:int, pending:int} */
    #[Computed]
    public function kpis(): array
    {
        $all = $this->source();

        return [
            'transferred' => (int) array_sum(array_map(fn (array $t) => (int) $t['amount'], $all)),
            'count' => count($all),
            'pending' => count(array_filter($all, fn (array $t) => $t['status'] === 'pending')),
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['form_amount', 'form_note']);
        $this->form_fromSafe = 'main';
        $this->form_toSafe = '';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $safeKeys = array_keys($this->safes());

        $this->validate([
            'form_fromSafe' => ['required', Rule::in($safeKeys)],
            'form_toSafe' => ['required', 'different:form_fromSafe', Rule::in($safeKeys)],
            'form_amount' => ['required', 'numeric', 'min:0.01'],
            'form_note' => ['nullable', 'string', 'max:200'],
        ], [
            'form_fromSafe.required' => __('txn.transfers.fromRequired'),
            'form_toSafe.required' => __('txn.transfers.toRequired'),
            'form_toSafe.different' => __('txn.transfers.mustDiffer'),
            'form_amount.required' => __('txn.transfers.amountRequired'),
            'form_amount.min' => __('txn.transfers.amountRequired'),
        ]);

        $payload = [
            'from_safe' => $this->form_fromSafe,
            'to_safe' => $this->form_toSafe,
            'amount' => Money::toMinor((float) $this->form_amount),
            'note' => trim($this->form_note) ?: null,
        ];

        $result = $this->waqty(fn () => app(FinanceService::class)->createTransfer($payload), __('waqty.genericError'));

        if ($result || $this->usingFallback()) {
            $this->showForm = false;
            $this->loaded = null;
            unset($this->filtered, $this->paginated, $this->total, $this->kpis);
            $this->dispatch('notify', type: 'success', message: __('txn.transfers.toastCreated'));
        }
    }

    public function render()
    {
        return view('livewire.transactions.transfers');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'TRF1', 'reference' => 'TRF-100234', 'from_safe' => 'الخزنة الرئيسية', 'to_safe' => 'خزنة الفرع الثاني', 'amount' => 50000, 'status' => 'completed', 'date' => '2026-07-03 14:20:00'],
            ['uuid' => 'TRF2', 'reference' => 'TRF-100235', 'from_safe' => 'خزنة الاستقبال', 'to_safe' => 'الخزنة الرئيسية', 'amount' => 120000, 'status' => 'completed', 'date' => '2026-07-02 11:05:00'],
            ['uuid' => 'TRF3', 'reference' => 'TRF-100236', 'from_safe' => 'الخزنة الرئيسية', 'to_safe' => 'الخزنة النثرية', 'amount' => 30000, 'status' => 'pending', 'date' => '2026-07-01 09:40:00'],
            ['uuid' => 'TRF4', 'reference' => 'TRF-100237', 'from_safe' => 'خزنة الفرع الثاني', 'to_safe' => 'الخزنة الرئيسية', 'amount' => 75000, 'status' => 'completed', 'date' => '2026-06-30 16:15:00'],
            ['uuid' => 'TRF5', 'reference' => 'TRF-100238', 'from_safe' => 'الخزنة الرئيسية', 'to_safe' => 'الخزنة البنكية', 'amount' => 200000, 'status' => 'pending', 'date' => '2026-06-28 08:30:00'],
        ];
    }
}
