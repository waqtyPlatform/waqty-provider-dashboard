<?php

declare(strict_types=1);

namespace App\Livewire\Employees;

use App\Enums\UserRole;
use App\Services\Waqty\EmployeeHrService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use App\Support\CurrentProvider;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Payroll — Waqty')]
class Payroll extends Component
{
    use HandlesWaqtyErrors;

    public string $search = '';

    public string $statusFilter = 'all'; // all | draft | approved | paid

    public int $currentPage = 1;

    public int $perPage = 8;

    // Generate payroll modal
    public bool $showGenerate = false;

    public string $form_period = '';

    // Pay modal
    public bool $showPay = false;

    public ?string $payingUuid = null;

    public string $pay_method = 'bank';

    public string $pay_amount = '';

    public string $pay_notes = '';

    // Payslip breakdown modal
    public bool $showPayslip = false;

    public ?string $payslipUuid = null;

    /** Optimistic status transitions after approve/pay/bulk. @var array<string, string> */
    public array $overrides = [];

    /** @var array<int, array<string, mixed>>|null per-request memo */
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

    /** Only admins and managers may run payroll. */
    public function canManage(): bool
    {
        return app(CurrentProvider::class)->role() !== UserRole::Staff;
    }

    /** @return array<int, array<string, mixed>> */
    private function source(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $this->loaded = app(EmployeeHrService::class)->payroll(['per_page' => 100]);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = $this->fallbackData();
        }

        foreach ($this->loaded as &$row) {
            $uuid = (string) ($row['uuid'] ?? '');
            if ($uuid !== '' && isset($this->overrides[$uuid])) {
                $row['status'] = $this->overrides[$uuid];
            }
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
        $status = $this->statusFilter;

        return array_values(array_filter($this->source(), function (array $r) use ($search, $status) {
            $matchesSearch = $search === ''
                || str_contains(mb_strtolower((string) ($r['employee'] ?? '')), $search);

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

    /** @return array{total:int, pending:int, paid:int} */
    #[Computed]
    public function kpis(): array
    {
        $all = $this->source();
        $net = fn (array $r) => (int) ($r['net'] ?? 0);

        return [
            'total' => array_sum(array_map($net, $all)),
            'pending' => array_sum(array_map(fn (array $r) => ($r['status'] ?? '') !== 'paid' ? $net($r) : 0, $all)),
            'paid' => array_sum(array_map(fn (array $r) => ($r['status'] ?? '') === 'paid' ? $net($r) : 0, $all)),
        ];
    }

    public function pendingCount(): int
    {
        return count(array_filter($this->source(), fn (array $r) => ($r['status'] ?? '') === 'draft'));
    }

    /** @return array<string, mixed>|null */
    public function row(?string $uuid): ?array
    {
        return $uuid === null ? null : (collect($this->source())->firstWhere('uuid', $uuid) ?: null);
    }

    public function openGenerate(): void
    {
        if (! $this->canManage()) {
            return;
        }

        $this->form_period = Carbon::now()->format('Y-m');
        $this->resetValidation();
        $this->showGenerate = true;
    }

    public function generatePayroll(): void
    {
        if (! $this->canManage()) {
            return;
        }

        $this->validate([
            'form_period' => ['required', 'date_format:Y-m'],
        ], [
            'form_period.required' => __('emp.payroll.periodRequired'),
            'form_period.date_format' => __('emp.payroll.periodRequired'),
        ]);

        $result = $this->waqty(
            fn () => app(EmployeeHrService::class)->generatePayroll(['period' => $this->form_period]) ?? true,
            __('waqty.genericError')
        );

        if ($result || $this->usingFallback()) {
            $this->showGenerate = false;
            $this->loaded = null;
            unset($this->filtered, $this->paginated, $this->total, $this->kpis);
            $this->dispatch('notify', type: 'success', message: __('emp.payroll.generated'));
        }
    }

    public function approvePayroll(string $uuid): void
    {
        if (! $this->canManage()) {
            return;
        }

        $this->overrides[$uuid] = 'approved';

        if (! $this->usingFallback()) {
            $this->waqty(fn () => app(EmployeeHrService::class)->approvePayroll($uuid) ?? true, __('waqty.genericError'));
        }

        unset($this->filtered, $this->paginated, $this->total, $this->kpis);
        $this->dispatch('notify', type: 'success', message: __('emp.payroll.approved'));
    }

    public function openPay(string $uuid): void
    {
        if (! $this->canManage()) {
            return;
        }

        $row = $this->row($uuid);
        if (! $row) {
            return;
        }

        $this->payingUuid = $uuid;
        $this->pay_method = 'bank';
        $this->pay_amount = (string) Money::fromMinor((int) ($row['net'] ?? 0));
        $this->pay_notes = '';
        $this->resetValidation();
        $this->showPay = true;
    }

    public function payPayroll(): void
    {
        if (! $this->canManage() || $this->payingUuid === null) {
            return;
        }

        $this->validate([
            'pay_method' => ['required', 'in:cash,bank,cheque'],
            'pay_amount' => ['required', 'numeric', 'min:0.01'],
            'pay_notes' => ['nullable', 'string', 'max:300'],
        ], [
            'pay_method.required' => __('emp.payroll.methodRequired'),
            'pay_amount.required' => __('emp.payroll.amountRequired'),
            'pay_amount.min' => __('emp.payroll.amountPositive'),
        ]);

        $uuid = $this->payingUuid;
        $payload = [
            'method' => $this->pay_method,
            'amount' => Money::toMinor((float) $this->pay_amount),
            'notes' => trim($this->pay_notes),
        ];

        $this->overrides[$uuid] = 'paid';

        if (! $this->usingFallback()) {
            $this->waqty(fn () => app(EmployeeHrService::class)->payPayroll($uuid, $payload) ?? true, __('waqty.genericError'));
        }

        $this->showPay = false;
        $this->payingUuid = null;
        unset($this->filtered, $this->paginated, $this->total, $this->kpis);
        $this->dispatch('notify', type: 'success', message: __('emp.payroll.paid'));
    }

    public function openPayslip(string $uuid): void
    {
        $this->payslipUuid = $uuid;
        $this->showPayslip = true;
    }

    /** Approve every still-draft run in one pass. */
    public function processAllPending(): void
    {
        if (! $this->canManage()) {
            return;
        }

        $drafts = array_filter($this->source(), fn (array $r) => ($r['status'] ?? '') === 'draft');
        if ($drafts === []) {
            return;
        }

        foreach ($drafts as $row) {
            $uuid = (string) ($row['uuid'] ?? '');
            if ($uuid === '') {
                continue;
            }
            $this->overrides[$uuid] = 'approved';

            if (! $this->usingFallback()) {
                $this->waqty(fn () => app(EmployeeHrService::class)->approvePayroll($uuid) ?? true, __('waqty.genericError'));
            }
        }

        unset($this->filtered, $this->paginated, $this->total, $this->kpis);
        $this->dispatch('notify', type: 'success', message: __('emp.payroll.approved'));
    }

    public function render()
    {
        return view('livewire.employees.payroll');
    }

    /** Local Arabic sample payroll runs for graceful degradation. @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'PR1', 'employee' => 'سارة أحمد', 'period' => '2026-07', 'base_salary' => 800000, 'commissions' => 150000, 'deductions' => 20000, 'net' => 930000, 'status' => 'paid'],
            ['uuid' => 'PR2', 'employee' => 'منى عادل', 'period' => '2026-07', 'base_salary' => 650000, 'commissions' => 90000, 'deductions' => 5000, 'net' => 735000, 'status' => 'approved'],
            ['uuid' => 'PR3', 'employee' => 'خالد حسن', 'period' => '2026-07', 'base_salary' => 700000, 'commissions' => 60000, 'deductions' => 15000, 'net' => 745000, 'status' => 'draft'],
            ['uuid' => 'PR4', 'employee' => 'ياسمين فاروق', 'period' => '2026-07', 'base_salary' => 600000, 'commissions' => 120000, 'deductions' => 0, 'net' => 720000, 'status' => 'draft'],
            ['uuid' => 'PR5', 'employee' => 'طارق سامي', 'period' => '2026-07', 'base_salary' => 550000, 'commissions' => 40000, 'deductions' => 25000, 'net' => 565000, 'status' => 'approved'],
        ];
    }
}
