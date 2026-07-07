<?php

declare(strict_types=1);

namespace App\Livewire\Employees;

use App\Services\Waqty\EmployeeHrService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Transfers — Waqty')]
class Transfers extends Component
{
    use HandlesWaqtyErrors;

    public string $statusFilter = 'all'; // all | pending | approved | rejected

    public int $currentPage = 1;

    public int $perPage = 8;

    // Create slide-over
    public bool $showForm = false;

    public string $form_employee = '';

    public string $form_from_branch = '';

    public string $form_to_branch = '';

    public string $form_type = 'permanent'; // permanent | temporary

    public string $form_until_date = '';

    // Reject confirmation
    public bool $showReject = false;

    public ?string $rejectingUuid = null;

    public string $rejectReason = '';

    /** Optimistic status overrides keyed by transfer uuid. @var array<string, string> */
    public array $overrides = [];

    /** @var array<int, array<string, mixed>>|null per-request memo */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    public function updatedStatusFilter(): void
    {
        $this->currentPage = 1;
    }

    /**
     * All transfers (normalized) with optimistic overrides applied.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function transfers(): array
    {
        if ($this->loaded === null) {
            try {
                $rows = app(EmployeeHrService::class)->transfers(['per_page' => 100]);
            } catch (WaqtyApiException) {
                $this->fallbackUsed = true;
                $rows = $this->fallbackData();
            }

            $this->loaded = array_values(array_map(
                fn ($r) => $this->normalize(is_array($r) ? $r : []),
                $rows,
            ));
        }

        $rows = $this->loaded;
        foreach ($rows as $i => $row) {
            if (isset($this->overrides[$row['uuid']])) {
                $rows[$i]['status'] = $this->overrides[$row['uuid']];
            }
        }

        return $rows;
    }

    public function usingFallback(): bool
    {
        $this->transfers();

        return $this->fallbackUsed;
    }

    /** @return array<int, array<string, mixed>> */
    #[Computed]
    public function filtered(): array
    {
        $status = $this->statusFilter;

        return array_values(array_filter(
            $this->transfers,
            fn (array $r) => $status === 'all' || ($r['status'] ?? '') === $status,
        ));
    }

    /** @return array<int, array<string, mixed>> */
    #[Computed]
    public function paginated(): array
    {
        return array_slice($this->filtered, ($this->currentPage - 1) * $this->perPage, $this->perPage);
    }

    #[Computed]
    public function total(): int
    {
        return count($this->filtered);
    }

    /** Transfer type enum values (English); labels resolve via emp.transfers.type*. @return array<int, string> */
    public function types(): array
    {
        return ['permanent', 'temporary'];
    }

    /** Status enum values (English); labels resolve via emp.transfers.status*. @return array<int, string> */
    public function statuses(): array
    {
        return ['pending', 'approved', 'rejected'];
    }

    /** Sample roster used to populate the employee <select>. @return array<int, string> */
    public function employeeOptions(): array
    {
        return ['سارة أحمد', 'منى عادل', 'خالد حسن', 'ياسمين فاروق', 'طارق سامي', 'عمر نبيل'];
    }

    /** Sample branches used to populate the from/to <select>s. @return array<int, string> */
    public function branchOptions(): array
    {
        return ['وسط البلد', 'مول العرب', 'المعادي', 'مدينة نصر', 'الشيخ زايد'];
    }

    public function openCreate(): void
    {
        $this->reset(['form_employee', 'form_from_branch', 'form_to_branch', 'form_until_date']);
        $this->form_type = 'permanent';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function createTransfer(): void
    {
        $rules = [
            'form_employee' => ['required', 'string', 'max:120'],
            'form_from_branch' => ['required', 'string', 'max:120'],
            'form_to_branch' => ['required', 'string', 'max:120', 'different:form_from_branch'],
            'form_type' => ['required', 'in:permanent,temporary'],
        ];

        if ($this->form_type === 'temporary') {
            $rules['form_until_date'] = ['required', 'date', 'after:today'];
        }

        $this->validate($rules, [
            'form_employee.required' => __('emp.transfers.employeeRequired'),
            'form_from_branch.required' => __('emp.transfers.fromRequired'),
            'form_to_branch.required' => __('emp.transfers.toRequired'),
            'form_to_branch.different' => __('emp.transfers.branchesDiffer'),
            'form_until_date.required' => __('emp.transfers.untilRequired'),
            'form_until_date.after' => __('emp.transfers.untilFuture'),
        ]);

        $payload = [
            'employee' => trim($this->form_employee),
            'from_branch' => trim($this->form_from_branch),
            'to_branch' => trim($this->form_to_branch),
            'type' => $this->form_type,
            'until_date' => $this->form_type === 'temporary' ? $this->form_until_date : null,
        ];

        $result = $this->waqty(
            fn () => app(EmployeeHrService::class)->createTransfer($payload) ?? true,
            __('emp.transfers.saveFailed'),
        );

        if ($result || $this->usingFallback()) {
            $this->showForm = false;
            $this->loaded = null;
            unset($this->transfers, $this->filtered, $this->paginated, $this->total);
            $this->dispatch('notify', type: 'success', message: __('common.saved'));
        }
    }

    public function approveTransfer(string $uuid): void
    {
        $this->overrides[$uuid] = 'approved';

        if (! $this->usingFallback()) {
            $this->waqty(
                fn () => app(EmployeeHrService::class)->approveTransfer($uuid) ?? true,
                __('emp.transfers.saveFailed'),
            );
        }

        unset($this->transfers, $this->filtered, $this->paginated, $this->total);
        $this->dispatch('notify', type: 'success', message: __('emp.transfers.approved'));
    }

    public function confirmReject(string $uuid): void
    {
        $this->rejectingUuid = $uuid;
        $this->rejectReason = '';
        $this->showReject = true;
    }

    public function rejectTransfer(): void
    {
        if (! $this->rejectingUuid) {
            return;
        }

        $uuid = $this->rejectingUuid;
        $reason = trim($this->rejectReason);
        $this->overrides[$uuid] = 'rejected';

        if (! $this->usingFallback()) {
            $this->waqty(
                fn () => app(EmployeeHrService::class)->rejectTransfer($uuid, $reason) ?? true,
                __('emp.transfers.saveFailed'),
            );
        }

        $this->showReject = false;
        $this->rejectingUuid = null;
        $this->rejectReason = '';
        unset($this->transfers, $this->filtered, $this->paginated, $this->total);
        $this->dispatch('notify', type: 'success', message: __('emp.transfers.rejected'));
    }

    public function render()
    {
        return view('livewire.employees.transfers');
    }

    /**
     * Shape a raw transfer row into the keys the view relies on.
     *
     * @param  array<string, mixed>  $r
     * @return array<string, mixed>
     */
    private function normalize(array $r): array
    {
        return [
            'uuid' => (string) ($r['uuid'] ?? $r['id'] ?? ''),
            'employee' => $this->label($r['employee'] ?? $r['employee_name'] ?? ''),
            'from_branch' => $this->label($r['from_branch'] ?? $r['from_branch_name'] ?? $r['fromBranch'] ?? ''),
            'to_branch' => $this->label($r['to_branch'] ?? $r['to_branch_name'] ?? $r['toBranch'] ?? ''),
            'type' => (string) ($r['type'] ?? 'permanent'),
            'until_date' => isset($r['until_date']) ? (string) $r['until_date'] : ($r['until'] ?? null),
            'status' => (string) ($r['status'] ?? 'pending'),
        ];
    }

    /** Extract a display name from either a nested object or a plain string. */
    private function label(mixed $v): string
    {
        if (is_array($v)) {
            return (string) ($v['name'] ?? $v['title'] ?? '');
        }

        return (string) $v;
    }

    /** Local Arabic sample transfers for graceful degradation. @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'TR1', 'employee' => 'سارة أحمد', 'from_branch' => 'وسط البلد', 'to_branch' => 'مول العرب', 'type' => 'permanent', 'until_date' => null, 'status' => 'pending'],
            ['uuid' => 'TR2', 'employee' => 'خالد حسن', 'from_branch' => 'مول العرب', 'to_branch' => 'المعادي', 'type' => 'temporary', 'until_date' => '2026-08-15', 'status' => 'approved'],
            ['uuid' => 'TR3', 'employee' => 'منى عادل', 'from_branch' => 'المعادي', 'to_branch' => 'مدينة نصر', 'type' => 'temporary', 'until_date' => '2026-07-20', 'status' => 'pending'],
            ['uuid' => 'TR4', 'employee' => 'ياسمين فاروق', 'from_branch' => 'الشيخ زايد', 'to_branch' => 'وسط البلد', 'type' => 'permanent', 'until_date' => null, 'status' => 'rejected'],
            ['uuid' => 'TR5', 'employee' => 'طارق سامي', 'from_branch' => 'مدينة نصر', 'to_branch' => 'الشيخ زايد', 'type' => 'temporary', 'until_date' => '2026-09-01', 'status' => 'approved'],
        ];
    }
}
