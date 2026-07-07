<?php

declare(strict_types=1);

namespace App\Livewire\Employees;

use App\Services\Waqty\EmployeeHrService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Deductions — Waqty')]
class Deductions extends Component
{
    use HandlesWaqtyErrors;

    public string $search = '';

    public string $typeFilter = 'all'; // all | absence | late | penalty | advance | other

    public int $currentPage = 1;

    public int $perPage = 8;

    // Create/edit slide-over
    public bool $showForm = false;

    public ?string $editingUuid = null;

    public string $form_employee = '';

    public string $form_type = 'absence';

    public string $form_amount = '';

    public string $form_reason = '';

    public string $form_date = '';

    // Delete confirmation
    public bool $showDelete = false;

    public ?string $deletingUuid = null;

    /** @var array<int, array<string, mixed>>|null per-request memo */
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

    /** @return array<int, array<string, mixed>> */
    private function source(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $this->loaded = app(EmployeeHrService::class)->deductions(['per_page' => 100]);
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
        $type = $this->typeFilter;

        return array_values(array_filter($this->source(), function (array $r) use ($search, $type) {
            $matchesSearch = $search === ''
                || str_contains(mb_strtolower((string) ($r['employee'] ?? '')), $search)
                || str_contains(mb_strtolower((string) ($r['reason'] ?? '')), $search);

            return $matchesSearch && ($type === 'all' || ($r['type'] ?? '') === $type);
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

    /** @return array{total:int, thisMonth:int, count:int} */
    #[Computed]
    public function kpis(): array
    {
        $all = $this->source();
        $month = Carbon::now()->format('Y-m');

        return [
            'total' => array_sum(array_map(fn (array $r) => (int) ($r['amount'] ?? 0), $all)),
            'thisMonth' => array_sum(array_map(
                fn (array $r) => str_starts_with((string) ($r['date'] ?? ''), $month) ? (int) ($r['amount'] ?? 0) : 0,
                $all
            )),
            'count' => count($all),
        ];
    }

    /** Deduction type enum values (English); labels resolve via emp.deductions.type*. @return array<int, string> */
    public function types(): array
    {
        return ['absence', 'late', 'penalty', 'advance', 'other'];
    }

    /** Sample roster used to populate the employee <select>. @return array<int, string> */
    public function employeeOptions(): array
    {
        return ['سارة أحمد', 'منى عادل', 'خالد حسن', 'ياسمين فاروق', 'طارق سامي', 'عمر نبيل'];
    }

    public function openCreate(): void
    {
        $this->reset(['editingUuid', 'form_employee', 'form_amount', 'form_reason', 'form_date']);
        $this->form_type = 'absence';
        $this->form_date = Carbon::now()->format('Y-m-d');
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $uuid): void
    {
        $row = collect($this->source())->firstWhere('uuid', $uuid);
        if (! $row) {
            return;
        }

        $this->editingUuid = $uuid;
        $this->form_employee = (string) ($row['employee'] ?? '');
        $this->form_type = (string) ($row['type'] ?? 'absence');
        $this->form_amount = (string) Money::fromMinor((int) ($row['amount'] ?? 0));
        $this->form_reason = (string) ($row['reason'] ?? '');
        $this->form_date = (string) ($row['date'] ?? '');
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_employee' => ['required', 'string', 'max:120'],
            'form_type' => ['required', 'in:absence,late,penalty,advance,other'],
            'form_amount' => ['required', 'numeric', 'min:0.01'],
            'form_reason' => ['required', 'string', 'max:300'],
            'form_date' => ['required', 'date'],
        ], [
            'form_employee.required' => __('emp.deductions.employeeRequired'),
            'form_type.required' => __('emp.deductions.typeRequired'),
            'form_amount.required' => __('emp.deductions.amountRequired'),
            'form_amount.min' => __('emp.deductions.amountPositive'),
            'form_reason.required' => __('emp.deductions.reasonRequired'),
            'form_date.required' => __('emp.deductions.dateRequired'),
        ]);

        $payload = [
            'employee' => trim($this->form_employee),
            'type' => $this->form_type,
            'amount' => Money::toMinor((float) $this->form_amount),
            'reason' => trim($this->form_reason),
            'date' => $this->form_date,
        ];

        $result = $this->waqty(function () use ($payload) {
            $service = app(EmployeeHrService::class);
            $this->editingUuid
                ? $service->updateDeduction($this->editingUuid, $payload)
                : $service->createDeduction($payload);

            return true;
        }, __('waqty.genericError'));

        if ($result || $this->usingFallback()) {
            $this->showForm = false;
            $this->editingUuid = null;
            $this->loaded = null;
            unset($this->filtered, $this->paginated, $this->total, $this->kpis);
            $this->dispatch('notify', type: 'success', message: __('common.saved'));
        }
    }

    public function confirmDelete(string $uuid): void
    {
        $this->deletingUuid = $uuid;
        $this->showDelete = true;
    }

    public function deleteDeduction(): void
    {
        if (! $this->deletingUuid) {
            return;
        }

        $uuid = $this->deletingUuid;
        $result = $this->waqty(fn () => app(EmployeeHrService::class)->deleteDeduction($uuid) ?? true, __('waqty.genericError'));

        $this->showDelete = false;
        $this->deletingUuid = null;

        if ($result || $this->usingFallback()) {
            $this->loaded = null;
            unset($this->filtered, $this->paginated, $this->total, $this->kpis);
            $this->dispatch('notify', type: 'success', message: __('common.deleted'));
        }
    }

    public function render()
    {
        return view('livewire.employees.deductions');
    }

    /** Local Arabic sample deductions for graceful degradation. @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'D1', 'employee' => 'سارة أحمد', 'type' => 'absence', 'amount' => 15000, 'reason' => 'غياب بدون إذن مسبق', 'date' => '2026-07-05'],
            ['uuid' => 'D2', 'employee' => 'خالد حسن', 'type' => 'late', 'amount' => 5000, 'reason' => 'تأخير متكرر عن موعد الحضور', 'date' => '2026-07-04'],
            ['uuid' => 'D3', 'employee' => 'منى عادل', 'type' => 'penalty', 'amount' => 25000, 'reason' => 'مخالفة سياسة الصالون', 'date' => '2026-07-02'],
            ['uuid' => 'D4', 'employee' => 'ياسمين فاروق', 'type' => 'advance', 'amount' => 100000, 'reason' => 'سلفة على الراتب', 'date' => '2026-06-28'],
            ['uuid' => 'D5', 'employee' => 'طارق سامي', 'type' => 'other', 'amount' => 8000, 'reason' => 'خصم بند آخر', 'date' => '2026-06-24'],
        ];
    }
}
