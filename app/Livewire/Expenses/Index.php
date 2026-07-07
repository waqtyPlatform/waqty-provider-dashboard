<?php

declare(strict_types=1);

namespace App\Livewire\Expenses;

use App\Data\Waqty\ExpenseData;
use App\Services\Waqty\ExpenseService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Expenses — Waqty')]
class Index extends Component
{
    use HandlesWaqtyErrors;

    public string $search = '';

    public string $categoryFilter = 'all';

    public string $statusFilter = 'all';

    public int $currentPage = 1;

    public int $perPage = 10;

    // Add slide-over
    public bool $showForm = false;

    public string $form_description = '';

    public string $form_amount = '';

    public string $form_category = 'Supplies';

    public string $form_vendor = '';

    public string $form_method = 'cash';

    public string $form_date = '';

    // Delete confirmation
    public bool $showDelete = false;

    public ?string $deletingUuid = null;

    /** Optimistic status after approve/reject. @var array<string, string> */
    public array $overrides = [];

    /** @var array<int, ExpenseData>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    public function mount(): void
    {
        $this->form_date = Carbon::today()->toDateString();
    }

    public function updatedSearch(): void
    {
        $this->currentPage = 1;
    }

    /** @return array<int, ExpenseData> */
    private function source(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $this->loaded = app(ExpenseService::class)->expenses(['per_page' => 100]);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = array_map(fn ($a) => ExpenseData::from($a), $this->fallbackData());
        }

        foreach ($this->loaded as $e) {
            if (isset($this->overrides[$e->uuid])) {
                $e->status = $this->overrides[$e->uuid];
            }
        }

        return $this->loaded;
    }

    public function usingFallback(): bool
    {
        $this->source();

        return $this->fallbackUsed;
    }

    /** @return array<int, ExpenseData> */
    #[Computed]
    public function filtered(): array
    {
        $search = trim(mb_strtolower($this->search));
        $category = $this->categoryFilter;
        $status = $this->statusFilter;

        return array_values(array_filter($this->source(), function (ExpenseData $e) use ($search, $category, $status) {
            $matchesSearch = $search === ''
                || str_contains(mb_strtolower((string) $e->description), $search)
                || str_contains(mb_strtolower((string) $e->vendor), $search);

            return $matchesSearch
                && ($category === 'all' || $e->category === $category)
                && ($status === 'all' || $e->status === $status);
        }));
    }

    /** @return array<int, ExpenseData> */
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

    /** @return array{total:int, pending:int, topCategory:string} */
    #[Computed]
    public function kpis(): array
    {
        $all = $this->source();
        $byCat = [];
        foreach ($all as $e) {
            $byCat[$e->category ?? '—'] = ($byCat[$e->category ?? '—'] ?? 0) + $e->amount;
        }
        arsort($byCat);
        $topCat = (string) (array_key_first($byCat) ?? '—');

        return [
            'total' => array_sum(array_map(fn (ExpenseData $e) => $e->amount, $all)),
            'pending' => count(array_filter($all, fn (ExpenseData $e) => $e->status === 'pending')),
            'topCategory' => $topCat === '—' ? '—' : __('exp.cat.'.$topCat),
        ];
    }

    /** @return array<int, string> */
    public function categories(): array
    {
        return ['Supplies', 'Rent', 'Utilities', 'Marketing', 'Equipment', 'Salary', 'Maintenance'];
    }

    public function openCreate(): void
    {
        $this->reset(['form_description', 'form_amount', 'form_vendor']);
        $this->form_category = 'Supplies';
        $this->form_method = 'cash';
        $this->form_date = Carbon::today()->toDateString();
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_description' => ['required', 'string', 'max:300'],
            'form_amount' => ['required', 'numeric', 'min:0.01'],
            'form_category' => ['required', 'string'],
            'form_vendor' => ['nullable', 'string', 'max:100'],
            'form_method' => ['required', 'in:cash,card,transfer'],
            'form_date' => ['required', 'date'],
        ], [
            'form_description.required' => __('expenses.descRequired'),
            'form_amount.required' => __('expenses.amountRequired'),
            'form_amount.min' => __('expenses.amountRequired'),
        ]);

        $payload = [
            'description' => trim($this->form_description),
            'amount' => Money::toMinor((float) $this->form_amount),
            'category' => $this->form_category,
            'vendor' => trim($this->form_vendor) ?: null,
            'payment_method' => $this->form_method,
            'date' => $this->form_date,
        ];

        $result = $this->waqty(fn () => app(ExpenseService::class)->create($payload), __('waqty.genericError'));

        if ($result || $this->usingFallback()) {
            $this->showForm = false;
            $this->loaded = null;
            unset($this->filtered, $this->paginated, $this->total, $this->kpis);
            $this->dispatch('notify', type: 'success', message: __('expenses.toastAdded'));
        }
    }

    public function approve(string $uuid): void
    {
        $this->moderate($uuid, 'approved', fn () => app(ExpenseService::class)->approve($uuid));
    }

    public function reject(string $uuid): void
    {
        $this->moderate($uuid, 'rejected', fn () => app(ExpenseService::class)->reject($uuid));
    }

    private function moderate(string $uuid, string $status, callable $call): void
    {
        $this->overrides[$uuid] = $status;

        if (! $this->usingFallback()) {
            $this->waqty(fn () => $call() ?? true, __('waqty.genericError'));
        }

        unset($this->filtered, $this->paginated, $this->total, $this->kpis);
        $this->dispatch('notify', type: 'success', message: __('common.saved'));
    }

    public function confirmDelete(string $uuid): void
    {
        $this->deletingUuid = $uuid;
        $this->showDelete = true;
    }

    public function deleteExpense(): void
    {
        if (! $this->deletingUuid) {
            return;
        }

        $uuid = $this->deletingUuid;
        $result = $this->waqty(fn () => app(ExpenseService::class)->delete($uuid) ?? true, __('waqty.genericError'));

        $this->showDelete = false;
        $this->deletingUuid = null;

        if ($result || $this->usingFallback()) {
            $this->loaded = null;
            unset($this->filtered, $this->paginated, $this->total, $this->kpis);
            $this->dispatch('notify', type: 'success', message: __('expenses.toastDeleted'));
        }
    }

    public function render()
    {
        return view('livewire.expenses.index');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'X1', 'category' => 'Rent', 'vendor' => 'عقارات القاهرة', 'description' => 'إيجار الفرع الشهري', 'amount' => 1500000, 'status' => 'approved', 'payment_method' => 'transfer', 'date' => '2026-07-01'],
            ['uuid' => 'X2', 'category' => 'Supplies', 'vendor' => 'مستودع الجمال', 'description' => 'إعادة تخزين صبغة الشعر', 'amount' => 320000, 'status' => 'approved', 'payment_method' => 'card', 'date' => '2026-06-28'],
            ['uuid' => 'X3', 'category' => 'Utilities', 'vendor' => 'شركة الكهرباء', 'description' => 'فاتورة الكهرباء', 'amount' => 180000, 'status' => 'pending', 'payment_method' => 'cash', 'date' => '2026-06-25'],
            ['uuid' => 'X4', 'category' => 'Marketing', 'vendor' => 'إعلانات ميتا', 'description' => 'حملة إنستجرام', 'amount' => 250000, 'status' => 'pending', 'payment_method' => 'card', 'date' => '2026-06-20'],
            ['uuid' => 'X5', 'category' => 'Equipment', 'vendor' => 'مستلزمات الصالون', 'description' => 'كراسي تصفيف جديدة', 'amount' => 640000, 'status' => 'approved', 'payment_method' => 'transfer', 'date' => '2026-06-15'],
            ['uuid' => 'X6', 'category' => 'Maintenance', 'vendor' => 'خدمات الإصلاح', 'description' => 'صيانة التكييف', 'amount' => 45000, 'status' => 'rejected', 'payment_method' => 'cash', 'date' => '2026-06-10'],
        ];
    }
}
