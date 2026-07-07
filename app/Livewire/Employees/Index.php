<?php

declare(strict_types=1);

namespace App\Livewire\Employees;

use App\Data\Waqty\EmployeeData;
use App\Services\Waqty\EmployeeService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use App\Support\CurrentProvider;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Team — Waqty')]
class Index extends Component
{
    use HandlesWaqtyErrors;

    public string $search = '';

    public string $statusFilter = 'all'; // all | active | blocked | inactive

    public int $currentPage = 1;

    public int $perPage = 8;

    // Create/edit slide-over
    public bool $showForm = false;

    public ?string $editingUuid = null;

    public string $form_name = '';

    public string $form_email = '';

    public string $form_phone = '';

    public string $form_branch = '';

    public string $form_role = 'staff';

    public string $form_position = '';

    public string $form_password = '';

    // Delete confirmation
    public bool $showDelete = false;

    public ?string $deletingUuid = null;

    /** Optimistic active/blocked state applied on top of the fetched list. @var array<string, array{active?:bool, blocked?:bool}> */
    public array $overrides = [];

    /** @var array<int, EmployeeData>|null per-request memo */
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

    /** @return array<int, EmployeeData> */
    private function source(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $this->loaded = app(EmployeeService::class)->employees();
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = array_map(fn ($a) => EmployeeData::from($a), $this->fallbackData());
        }

        // Apply optimistic toggle overrides.
        foreach ($this->loaded as $emp) {
            if (isset($this->overrides[$emp->uuid])) {
                $o = $this->overrides[$emp->uuid];
                $emp->active = $o['active'] ?? $emp->active;
                $emp->blocked = $o['blocked'] ?? $emp->blocked;
            }
        }

        return $this->loaded;
    }

    public function usingFallback(): bool
    {
        $this->source();

        return $this->fallbackUsed;
    }

    /** @return array<int, EmployeeData> */
    #[Computed]
    public function filtered(): array
    {
        $search = trim(mb_strtolower($this->search));
        $status = $this->statusFilter;

        return array_values(array_filter($this->source(), function (EmployeeData $e) use ($search, $status) {
            $matchesSearch = $search === ''
                || str_contains(mb_strtolower((string) $e->name), $search)
                || str_contains(mb_strtolower((string) $e->email), $search)
                || str_contains(mb_strtolower((string) $e->phone), $search);

            $matchesStatus = match ($status) {
                'active' => $e->active && ! $e->blocked,
                'blocked' => $e->blocked,
                'inactive' => ! $e->active,
                default => true,
            };

            return $matchesSearch && $matchesStatus;
        }));
    }

    /** @return array<int, EmployeeData> */
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

    /** @return array{total:int, active:int, blocked:int, branches:int} */
    #[Computed]
    public function kpis(): array
    {
        $all = $this->source();

        return [
            'total' => count($all),
            'active' => count(array_filter($all, fn (EmployeeData $e) => $e->active && ! $e->blocked)),
            'blocked' => count(array_filter($all, fn (EmployeeData $e) => $e->blocked)),
            'branches' => count(array_unique(array_filter(array_map(fn (EmployeeData $e) => $e->branchName(), $all)))),
        ];
    }

    /** @return array<string, string> uuid => name for the branch <select> */
    #[Computed]
    public function branchOptions(): array
    {
        $options = [];
        foreach (app(CurrentProvider::class)->branches() as $b) {
            if (isset($b['uuid'], $b['name'])) {
                $options[$b['uuid']] = $b['name'];
            }
        }

        // Fall back to branches referenced by the employees themselves.
        if ($options === []) {
            foreach ($this->source() as $e) {
                if ($e->branch_uuid && $e->branchName()) {
                    $options[$e->branch_uuid] = $e->branchName();
                }
            }
        }

        return $options;
    }

    public function openCreate(): void
    {
        $this->reset(['editingUuid', 'form_name', 'form_email', 'form_phone', 'form_branch', 'form_position', 'form_password']);
        $this->form_role = 'staff';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $uuid): void
    {
        $employee = collect($this->source())->firstWhere('uuid', $uuid);
        if (! $employee) {
            return;
        }

        $this->editingUuid = $uuid;
        $this->form_name = (string) $employee->name;
        $this->form_email = (string) $employee->email;
        $this->form_phone = (string) $employee->phone;
        $this->form_branch = (string) $employee->branch_uuid;
        $this->form_role = $employee->role ?: 'staff';
        $this->form_position = (string) $employee->position;
        $this->form_password = '';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $rules = [
            'form_name' => ['required', 'string', 'max:100'],
            'form_email' => ['nullable', 'email'],
            'form_phone' => ['nullable', 'string', 'min:8', 'max:20'],
            'form_branch' => ['nullable', 'string'],
            'form_role' => ['required', 'in:admin,manager,staff'],
            'form_position' => ['nullable', 'string', 'max:100'],
        ];
        if (! $this->editingUuid) {
            $rules['form_password'] = ['required', 'string', 'min:6'];
        }
        $this->validate($rules);

        $payload = array_filter([
            'name' => trim($this->form_name),
            'email' => trim($this->form_email) ?: null,
            'phone' => trim($this->form_phone) ?: null,
            'branch_uuid' => $this->form_branch ?: null,
            'role' => $this->form_role,
            'position' => trim($this->form_position) ?: null,
            'password' => $this->form_password ?: null,
        ], fn ($v) => $v !== null);

        $service = app(EmployeeService::class);

        $result = $this->waqty(function () use ($service, $payload) {
            $this->editingUuid
                ? $service->update($this->editingUuid, $payload)
                : $service->create($payload);

            return true;
        }, __('waqty.genericError'));

        if ($result) {
            $this->showForm = false;
            $this->dispatch('notify', type: 'success', message: $this->editingUuid ? __('employees.toastUpdated') : __('employees.toastCredsAdded'));
            $this->editingUuid = null;
            unset($this->filtered, $this->paginated, $this->total, $this->kpis);
        }
    }

    public function toggleActive(string $uuid): void
    {
        $emp = collect($this->source())->firstWhere('uuid', $uuid);
        if (! $emp) {
            return;
        }
        $next = ! $emp->active;
        $this->overrides[$uuid]['active'] = $next;

        if (! $this->usingFallback()) {
            $this->waqty(fn () => app(EmployeeService::class)->toggleActive($uuid, $next) ?? true, __('waqty.genericError'));
        }

        unset($this->filtered, $this->paginated, $this->total, $this->kpis);
    }

    public function toggleBlock(string $uuid): void
    {
        $emp = collect($this->source())->firstWhere('uuid', $uuid);
        if (! $emp) {
            return;
        }
        $next = ! $emp->blocked;
        $this->overrides[$uuid]['blocked'] = $next;

        if (! $this->usingFallback()) {
            $this->waqty(fn () => app(EmployeeService::class)->toggleBlock($uuid, $next) ?? true, __('waqty.genericError'));
        }

        unset($this->filtered, $this->paginated, $this->total, $this->kpis);
    }

    public function confirmDelete(string $uuid): void
    {
        $this->deletingUuid = $uuid;
        $this->showDelete = true;
    }

    public function deleteEmployee(): void
    {
        if (! $this->deletingUuid) {
            return;
        }

        $service = app(EmployeeService::class);
        $uuid = $this->deletingUuid;

        $result = $this->waqty(fn () => $service->delete($uuid) ?? true, __('waqty.genericError'));

        $this->showDelete = false;
        $this->deletingUuid = null;

        if ($result) {
            $this->dispatch('notify', type: 'success', message: __('employees.toastRemoveSuccess'));
            unset($this->filtered, $this->paginated, $this->total, $this->kpis);
        }
    }

    public function render()
    {
        return view('livewire.employees.index');
    }

    /** Sample team mirroring the source fallback for graceful degradation. */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'E001', 'name' => 'د. سارة أحمد', 'email' => 'sara@waqty.com', 'phone' => '01012345678', 'branch_uuid' => 'B1', 'branch' => ['uuid' => 'B1', 'name' => 'وسط البلد'], 'active' => true, 'blocked' => false, 'role' => 'admin', 'position' => 'المالك'],
            ['uuid' => 'E002', 'name' => 'منى عادل', 'email' => 'mona@waqty.com', 'phone' => '01112345678', 'branch_uuid' => 'B1', 'branch' => ['uuid' => 'B1', 'name' => 'وسط البلد'], 'active' => true, 'blocked' => false, 'role' => 'manager', 'position' => 'مدير الفرع'],
            ['uuid' => 'E003', 'name' => 'خالد حسن', 'email' => 'khaled@waqty.com', 'phone' => '01212345678', 'branch_uuid' => 'B2', 'branch' => ['uuid' => 'B2', 'name' => 'القاهرة الجديدة'], 'active' => true, 'blocked' => false, 'role' => 'staff', 'position' => 'مصفف شعر'],
            ['uuid' => 'E004', 'name' => 'ياسمين فاروق', 'email' => 'yasmin@waqty.com', 'phone' => '01512345678', 'branch_uuid' => 'B2', 'branch' => ['uuid' => 'B2', 'name' => 'القاهرة الجديدة'], 'active' => true, 'blocked' => false, 'role' => 'staff', 'position' => 'معالج'],
            ['uuid' => 'E005', 'name' => 'عمر نبيل', 'email' => 'omar@waqty.com', 'phone' => '01087654321', 'branch_uuid' => 'B1', 'branch' => ['uuid' => 'B1', 'name' => 'وسط البلد'], 'active' => false, 'blocked' => false, 'role' => 'staff', 'position' => 'موظف استقبال'],
            ['uuid' => 'E006', 'name' => 'ليلى مصطفى', 'email' => 'laila@waqty.com', 'phone' => '01198765432', 'branch_uuid' => 'B2', 'branch' => ['uuid' => 'B2', 'name' => 'القاهرة الجديدة'], 'active' => true, 'blocked' => true, 'role' => 'staff', 'position' => 'مصفف شعر'],
            ['uuid' => 'E007', 'name' => 'طارق سامي', 'email' => 'tarek@waqty.com', 'phone' => '01033221144', 'branch_uuid' => 'B1', 'branch' => ['uuid' => 'B1', 'name' => 'وسط البلد'], 'active' => true, 'blocked' => false, 'role' => 'staff', 'position' => 'حلاق'],
            ['uuid' => 'E008', 'name' => 'نور الدين', 'email' => 'nour@waqty.com', 'phone' => '01234567890', 'branch_uuid' => 'B2', 'branch' => ['uuid' => 'B2', 'name' => 'القاهرة الجديدة'], 'active' => true, 'blocked' => false, 'role' => 'staff', 'position' => 'فني أظافر'],
            ['uuid' => 'E009', 'name' => 'هناء فتحي', 'email' => 'hana@waqty.com', 'phone' => '01555443322', 'branch_uuid' => 'B1', 'branch' => ['uuid' => 'B1', 'name' => 'وسط البلد'], 'active' => true, 'blocked' => false, 'role' => 'manager', 'position' => 'العمليات'],
        ];
    }
}
