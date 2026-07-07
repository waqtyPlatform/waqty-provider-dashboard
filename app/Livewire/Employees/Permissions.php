<?php

declare(strict_types=1);

namespace App\Livewire\Employees;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Employees › Permissions — a per-employee permission OVERRIDE matrix.
 *
 * Admin-gated: pick an employee, then fine-tune a module × action grid that
 * starts pre-filled from their base job role, flipping individual cells to
 * grant or revoke access. This is a local demo — the source page has no live
 * permission-override endpoint, so both the base roles and the overrides live
 * in component state and Save just re-persists them locally and notifies.
 */
#[Layout('components.layouts.app')]
#[Title('Permissions — Waqty')]
class Permissions extends Component
{
    /** Modules access can be granted on (label = emp.perms.mod.*). */
    public const MODULES = ['dashboard', 'bookings', 'customers', 'employees', 'services', 'transactions', 'marketing', 'reports', 'settings'];

    public const ACTIONS = ['view', 'create', 'edit', 'delete'];

    /**
     * Base job roles: slug => [module => [action => bool]].
     *
     * @var array<string, array<string, array<string, bool>>>
     */
    public array $roles = [];

    /** @var array<int, array<string, string>> */
    public array $employees = [];

    public string $selectedUuid = '';

    /**
     * Working editable grid for the selected employee.
     *
     * @var array<string, array<string, bool>>
     */
    public array $form_perms = [];

    /**
     * Locally-saved overrides, keyed by employee uuid.
     *
     * @var array<string, array<string, array<string, bool>>>
     */
    public array $saved = [];

    public function mount(): void
    {
        $this->roles = $this->seedRoles();
        $this->employees = $this->seedEmployees();
        $this->selectedUuid = $this->employees[0]['uuid'] ?? '';
        $this->loadGrid();
    }

    /** Reload the grid whenever the chosen employee changes. */
    public function updatedSelectedUuid(): void
    {
        $this->loadGrid();
    }

    #[Computed]
    public function selectedEmployee(): ?array
    {
        return collect($this->employees)->firstWhere('uuid', $this->selectedUuid) ?: null;
    }

    /** Number of cells that differ from the employee's base role. */
    #[Computed]
    public function overrideCount(): int
    {
        if ($this->selectedUuid === '') {
            return 0;
        }
        $base = $this->baseGridFor($this->selectedUuid);
        $n = 0;
        foreach (self::MODULES as $m) {
            foreach (self::ACTIONS as $a) {
                if ((bool) ($this->form_perms[$m][$a] ?? false) !== (bool) ($base[$m][$a] ?? false)) {
                    $n++;
                }
            }
        }

        return $n;
    }

    /** Whether a single cell has been flipped away from the base role. */
    public function isOverridden(string $module, string $action): bool
    {
        if ($this->selectedUuid === '') {
            return false;
        }
        $base = $this->baseGridFor($this->selectedUuid);

        return (bool) ($this->form_perms[$module][$action] ?? false) !== (bool) ($base[$module][$action] ?? false);
    }

    /** Quick-set a whole module row: grant all, deny all, or restore the role. */
    public function setRow(string $module, string $level): void
    {
        if ($this->selectedUuid === '' || ! isset($this->form_perms[$module])) {
            return;
        }
        $this->form_perms[$module] = match ($level) {
            'full' => array_fill_keys(self::ACTIONS, true),
            'none' => array_fill_keys(self::ACTIONS, false),
            default => $this->baseGridFor($this->selectedUuid)[$module],
        };
    }

    /** Discard every override and fall back to the base role grid. */
    public function resetAll(): void
    {
        if ($this->selectedUuid === '') {
            return;
        }
        $this->form_perms = $this->baseGridFor($this->selectedUuid);
    }

    public function save(): void
    {
        if ($this->selectedUuid === '') {
            return;
        }
        // Local no-op: there is no live override endpoint, so keep the grid in
        // component state so switching employees and back preserves the edits.
        $this->saved[$this->selectedUuid] = $this->form_perms;
        $this->dispatch('notify', type: 'success', message: __('emp.perms.saved'));
    }

    public function render()
    {
        return view('livewire.employees.permissions');
    }

    /** Blank module×action grid, all denied. @return array<string, array<string, bool>> */
    private function blankPerms(): array
    {
        $grid = [];
        foreach (self::MODULES as $m) {
            $grid[$m] = array_fill_keys(self::ACTIONS, false);
        }

        return $grid;
    }

    /** Base grid for an employee's role, merged over a blank grid. @return array<string, array<string, bool>> */
    private function baseGridFor(string $uuid): array
    {
        $emp = collect($this->employees)->firstWhere('uuid', $uuid);
        $role = $this->roles[$emp['role'] ?? ''] ?? [];

        $grid = $this->blankPerms();
        foreach ($role as $module => $actions) {
            foreach ($actions as $action => $granted) {
                if (isset($grid[$module][$action])) {
                    $grid[$module][$action] = (bool) $granted;
                }
            }
        }

        return $grid;
    }

    /** Build the working grid from the employee's role, then apply any saved overrides. */
    private function loadGrid(): void
    {
        if ($this->selectedUuid === '') {
            $this->form_perms = $this->blankPerms();

            return;
        }
        $grid = $this->baseGridFor($this->selectedUuid);
        foreach (($this->saved[$this->selectedUuid] ?? []) as $module => $actions) {
            foreach ($actions as $action => $granted) {
                if (isset($grid[$module][$action])) {
                    $grid[$module][$action] = (bool) $granted;
                }
            }
        }
        $this->form_perms = $grid;
    }

    /** Base job roles (mirrors the Roles matrix seed). @return array<string, array<string, array<string, bool>>> */
    private function seedRoles(): array
    {
        $all = fn () => array_fill_keys(self::ACTIONS, true);
        $view = fn () => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false];

        $admin = [];
        $manager = [];
        $staff = [];
        foreach (self::MODULES as $m) {
            $admin[$m] = $all();
            $manager[$m] = in_array($m, ['settings'], true) ? $view() : $all();
            $staff[$m] = in_array($m, ['bookings', 'customers'], true)
                ? ['view' => true, 'create' => true, 'edit' => true, 'delete' => false]
                : (in_array($m, ['dashboard', 'services'], true) ? $view() : array_fill_keys(self::ACTIONS, false));
        }

        return ['admin' => $admin, 'manager' => $manager, 'staff' => $staff];
    }

    /** Arabic sample employees, each tied to a base role slug. @return array<int, array<string, string>> */
    private function seedEmployees(): array
    {
        return [
            ['uuid' => 'E1', 'name' => 'سارة أحمد', 'position' => 'كبيرة المصففين', 'role' => 'manager'],
            ['uuid' => 'E2', 'name' => 'منى عادل', 'position' => 'أخصائية بشرة', 'role' => 'staff'],
            ['uuid' => 'E3', 'name' => 'خالد حسن', 'position' => 'مدير الفرع', 'role' => 'admin'],
            ['uuid' => 'E4', 'name' => 'ياسمين فاروق', 'position' => 'موظفة استقبال', 'role' => 'staff'],
        ];
    }
}
