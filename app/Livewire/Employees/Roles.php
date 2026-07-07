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

/**
 * Employees › Roles & Permissions — a module × action permission matrix.
 * Lists roles with member counts; create/edit opens a matrix editor. Reads
 * from EmployeeHrService (roleApi) and falls back to local Arabic sample
 * data when the admin-only endpoint is unavailable.
 */
#[Layout('components.layouts.app')]
#[Title('Roles & Permissions — Waqty')]
class Roles extends Component
{
    use HandlesWaqtyErrors;

    /** Modules a role can be granted access to (label = emp.roles.mod.*). */
    public const MODULES = ['bookings', 'customers', 'employees', 'services', 'transactions', 'reports', 'marketing', 'settings', 'finance'];

    public const ACTIONS = ['view', 'create', 'edit', 'delete'];

    public bool $showForm = false;

    public ?string $editingUuid = null;

    public string $form_name = '';

    /** @var array<string, array<string, bool>> */
    public array $form_perms = [];

    public bool $showDelete = false;

    public ?string $deletingUuid = null;

    /** @var array<int, array<string, mixed>>|null per-request memo */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    /** @return array<int, array<string, mixed>> */
    #[Computed]
    public function roles(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $rows = app(EmployeeHrService::class)->roles();
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $rows = $this->fallbackData();
        }

        $this->loaded = array_values(array_map(
            fn ($r) => $this->normalize(is_array($r) ? $r : []),
            $rows,
        ));

        return $this->loaded;
    }

    public function usingFallback(): bool
    {
        $this->roles();

        return $this->fallbackUsed;
    }

    /** Blank module × action grid, all denied. @return array<string, array<string, bool>> */
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
        $this->reset(['editingUuid', 'form_name']);
        $this->form_perms = $this->blankPerms();
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $uuid): void
    {
        $role = collect($this->roles())->firstWhere('uuid', $uuid);
        if (! $role) {
            return;
        }
        $this->editingUuid = $uuid;
        $this->form_name = (string) $role['name'];
        $this->form_perms = $role['permissions'];
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
            ['form_name.required' => __('emp.roles.nameRequired')],
        );

        $payload = [
            'name' => trim($this->form_name),
            'permissions' => $this->form_perms,
        ];

        $result = $this->waqty(function () use ($payload) {
            $service = app(EmployeeHrService::class);
            $this->editingUuid
                ? $service->updateRole($this->editingUuid, $payload)
                : $service->createRole($payload);

            return true;
        }, __('emp.roles.saveFailed'));

        if ($result || $this->usingFallback()) {
            $this->showForm = false;
            $this->editingUuid = null;
            $this->loaded = null;
            unset($this->roles);
            $this->dispatch('notify', type: 'success', message: __('common.saved'));
        }
    }

    public function confirmDelete(string $uuid): void
    {
        $this->deletingUuid = $uuid;
        $this->showDelete = true;
    }

    public function deleteRole(): void
    {
        if (! $this->deletingUuid) {
            return;
        }
        $uuid = $this->deletingUuid;
        $result = $this->waqty(fn () => app(EmployeeHrService::class)->deleteRole($uuid) ?? true, __('emp.roles.deleteFailed'));

        $this->showDelete = false;
        $this->deletingUuid = null;

        if ($result || $this->usingFallback()) {
            $this->loaded = null;
            unset($this->roles);
            $this->dispatch('notify', type: 'success', message: __('common.deleted'));
        }
    }

    /**
     * Human summary of a role's overall access, for the list badge.
     *
     * @param  array<string, mixed>  $role
     */
    public function levelLabel(array $role): string
    {
        $granted = 0;
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
            return __('emp.roles.levelNone');
        }
        if ($fullModules === count(self::MODULES)) {
            return __('emp.roles.levelFull');
        }

        return __('emp.roles.levelCustom');
    }

    public function render()
    {
        return view('livewire.employees.roles');
    }

    /**
     * Shape a raw role row into the keys the view relies on, normalising the
     * permission map onto a complete module × action grid.
     *
     * @param  array<string, mixed>  $r
     * @return array<string, mixed>
     */
    private function normalize(array $r): array
    {
        $perms = $this->blankPerms();
        $raw = is_array($r['permissions'] ?? null) ? $r['permissions'] : [];
        foreach ($raw as $module => $actions) {
            if (! is_array($actions)) {
                continue;
            }
            foreach ($actions as $action => $granted) {
                if (isset($perms[$module][$action])) {
                    $perms[$module][$action] = (bool) $granted;
                }
            }
        }

        return [
            'uuid' => (string) ($r['uuid'] ?? $r['id'] ?? ''),
            'name' => (string) ($r['name'] ?? ''),
            'members' => (int) ($r['members'] ?? $r['members_count'] ?? $r['employees_count'] ?? $r['users_count'] ?? 0),
            'system' => (bool) ($r['system'] ?? $r['is_system'] ?? false),
            'permissions' => $perms,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        $all = fn () => array_fill_keys(self::ACTIONS, true);
        $view = fn () => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false];
        $none = fn () => array_fill_keys(self::ACTIONS, false);
        $manage = fn () => ['view' => true, 'create' => true, 'edit' => true, 'delete' => false];

        $manager = [];
        $supervisor = [];
        $staff = [];
        $accountant = [];
        foreach (self::MODULES as $m) {
            $manager[$m] = $all();
            $supervisor[$m] = in_array($m, ['settings', 'finance'], true) ? $view() : $all();
            $staff[$m] = in_array($m, ['bookings', 'customers', 'services'], true) ? $manage() : (in_array($m, ['reports'], true) ? $view() : $none());
            $accountant[$m] = in_array($m, ['transactions', 'reports', 'finance'], true) ? $all() : (in_array($m, ['bookings', 'customers'], true) ? $view() : $none());
        }

        return [
            ['uuid' => 'R1', 'name' => 'مدير', 'members' => 2, 'system' => true, 'permissions' => $manager],
            ['uuid' => 'R2', 'name' => 'مشرف', 'members' => 3, 'system' => false, 'permissions' => $supervisor],
            ['uuid' => 'R3', 'name' => 'موظف', 'members' => 8, 'system' => false, 'permissions' => $staff],
            ['uuid' => 'R4', 'name' => 'محاسب', 'members' => 2, 'system' => false, 'permissions' => $accountant],
        ];
    }
}
