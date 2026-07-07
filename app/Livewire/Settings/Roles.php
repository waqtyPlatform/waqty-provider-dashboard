<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Settings › Roles & Permissions — a module × action permission matrix.
 * Local-CRUD (the source page is partly mock and the roles endpoint is
 * admin-only) so state lives in a public array; no service/DTO/API.
 */
#[Layout('components.layouts.app')]
#[Title('Roles & Permissions — Waqty')]
class Roles extends Component
{
    /** Modules a role can be granted access to (label = settings.roles.mod.*). */
    public const MODULES = ['dashboard', 'bookings', 'customers', 'employees', 'services', 'transactions', 'marketing', 'reports', 'settings'];

    public const ACTIONS = ['view', 'create', 'edit', 'delete'];

    /** @var array<int, array<string, mixed>> */
    public array $roles = [];

    public bool $showForm = false;

    public ?string $editingId = null;

    public string $form_name = '';

    /** @var array<string, array<string, bool>> */
    public array $form_perms = [];

    public bool $showDelete = false;

    public ?string $deletingId = null;

    public function mount(): void
    {
        $this->roles = $this->seed();
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

    public function openCreate(): void
    {
        $this->reset(['editingId', 'form_name']);
        $this->form_perms = $this->blankPerms();
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $id): void
    {
        $role = collect($this->roles)->firstWhere('id', $id);
        if (! $role) {
            return;
        }
        $this->editingId = $id;
        $this->form_name = (string) $role['name'];
        // Merge saved perms over a blank grid so newly-added modules default to denied.
        $perms = $this->blankPerms();
        foreach (($role['permissions'] ?? []) as $module => $actions) {
            foreach ($actions as $action => $granted) {
                if (isset($perms[$module][$action])) {
                    $perms[$module][$action] = (bool) $granted;
                }
            }
        }
        $this->form_perms = $perms;
        $this->resetValidation();
        $this->showForm = true;
    }

    /** Quick-set an entire module row from the level buttons. */
    public function setLevel(string $module, string $level): void
    {
        if (! isset($this->form_perms[$module])) {
            return;
        }
        $this->form_perms[$module] = match ($level) {
            'full' => array_fill_keys(self::ACTIONS, true),
            'view' => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
            default => array_fill_keys(self::ACTIONS, false),
        };
    }

    public function save(): void
    {
        $this->validate(
            ['form_name' => ['required', 'string', 'max:60']],
            ['form_name.required' => __('settings.roles.nameRequired')],
        );

        if ($this->editingId) {
            $this->roles = array_map(function ($r) {
                if ($r['id'] === $this->editingId) {
                    $r['name'] = trim($this->form_name);
                    $r['permissions'] = $this->form_perms;
                }

                return $r;
            }, $this->roles);
            $message = __('settings.roles.permsUpdated');
        } else {
            $this->roles[] = [
                'id' => 'role-'.(count($this->roles) + 1).'-'.substr(md5($this->form_name), 0, 4),
                'name' => trim($this->form_name),
                'members' => 0,
                'system' => false,
                'permissions' => $this->form_perms,
            ];
            $message = __('settings.roles.created');
        }

        $this->showForm = false;
        $this->editingId = null;
        $this->dispatch('notify', type: 'success', message: $message);
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        $this->showDelete = true;
    }

    public function deleteRole(): void
    {
        if (! $this->deletingId) {
            return;
        }
        $this->roles = array_values(array_filter($this->roles, fn ($r) => $r['id'] !== $this->deletingId));
        $this->showDelete = false;
        $this->deletingId = null;
        $this->dispatch('notify', type: 'success', message: __('settings.roles.deleted'));
    }

    /** Human summary of a role's overall access, for the list badge. */
    public function levelLabel(array $role): string
    {
        $granted = 0;
        $totalModules = count(self::MODULES);
        $fullModules = 0;
        foreach (self::MODULES as $m) {
            $actions = $role['permissions'][$m] ?? [];
            $on = count(array_filter($actions));
            $granted += $on;
            if ($on === count(self::ACTIONS)) {
                $fullModules++;
            }
        }

        if ($granted === 0) {
            return __('settings.roles.levelNone');
        }
        if ($fullModules === $totalModules) {
            return __('settings.roles.levelFull');
        }

        return __('settings.roles.levelCustom');
    }

    public function render()
    {
        return view('livewire.settings.roles');
    }

    /** Default role set (mirrors the source seed). @return array<int, array<string, mixed>> */
    private function seed(): array
    {
        $all = fn () => array_fill_keys(self::ACTIONS, true);
        $view = fn () => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false];

        $admin = [];
        $manager = [];
        $staff = [];
        foreach (self::MODULES as $m) {
            $admin[$m] = $all();
            $manager[$m] = in_array($m, ['settings'], true) ? $view() : $all();
            $staff[$m] = in_array($m, ['bookings', 'customers'], true) ? ['view' => true, 'create' => true, 'edit' => true, 'delete' => false] : (in_array($m, ['dashboard', 'services'], true) ? $view() : array_fill_keys(self::ACTIONS, false));
        }

        return [
            ['id' => 'admin', 'name' => 'مدير النظام', 'members' => 2, 'system' => true, 'permissions' => $admin],
            ['id' => 'manager', 'name' => 'مدير', 'members' => 3, 'system' => true, 'permissions' => $manager],
            ['id' => 'staff', 'name' => 'موظف', 'members' => 8, 'system' => false, 'permissions' => $staff],
        ];
    }
}
