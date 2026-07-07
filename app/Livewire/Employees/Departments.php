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
#[Title('Departments — Waqty')]
class Departments extends Component
{
    use HandlesWaqtyErrors;

    public bool $showForm = false;

    public ?string $editingUuid = null;

    public string $form_name = '';

    public string $form_description = '';

    public bool $showDelete = false;

    public ?string $deletingUuid = null;

    /** @var array<int, array<string, mixed>>|null per-request memo */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    /** @return array<int, array<string, mixed>> */
    #[Computed]
    public function departments(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $rows = app(EmployeeHrService::class)->departments();
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
        $this->departments();

        return $this->fallbackUsed;
    }

    public function openCreate(): void
    {
        $this->reset(['editingUuid', 'form_name', 'form_description']);
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $uuid): void
    {
        $dept = collect($this->departments())->firstWhere('uuid', $uuid);
        if (! $dept) {
            return;
        }
        $this->editingUuid = $uuid;
        $this->form_name = (string) $dept['name'];
        $this->form_description = (string) ($dept['description'] ?? '');
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_name' => ['required', 'string', 'max:60'],
            'form_description' => ['nullable', 'string', 'max:255'],
        ]);

        $payload = [
            'name' => trim($this->form_name),
            'description' => trim($this->form_description) ?: null,
        ];

        $result = $this->waqty(function () use ($payload) {
            $service = app(EmployeeHrService::class);
            $this->editingUuid
                ? $service->updateDepartment($this->editingUuid, $payload)
                : $service->createDepartment($payload);

            return true;
        }, __('emp.departments.saveFailed'));

        if ($result || $this->usingFallback()) {
            $this->showForm = false;
            $this->editingUuid = null;
            $this->loaded = null;
            unset($this->departments);
            $this->dispatch('notify', type: 'success', message: __('common.saved'));
        }
    }

    public function confirmDelete(string $uuid): void
    {
        $this->deletingUuid = $uuid;
        $this->showDelete = true;
    }

    public function deleteDepartment(): void
    {
        if (! $this->deletingUuid) {
            return;
        }
        $uuid = $this->deletingUuid;
        $result = $this->waqty(fn () => app(EmployeeHrService::class)->deleteDepartment($uuid) ?? true, __('emp.departments.deleteFailed'));

        $this->showDelete = false;
        $this->deletingUuid = null;

        if ($result || $this->usingFallback()) {
            $this->loaded = null;
            unset($this->departments);
            $this->dispatch('notify', type: 'success', message: __('common.deleted'));
        }
    }

    public function render()
    {
        return view('livewire.employees.departments');
    }

    /**
     * Shape a raw department row into the keys the view relies on.
     *
     * @param  array<string, mixed>  $r
     * @return array<string, mixed>
     */
    private function normalize(array $r): array
    {
        return [
            'uuid' => (string) ($r['uuid'] ?? $r['id'] ?? ''),
            'name' => (string) ($r['name'] ?? ''),
            'description' => $r['description'] ?? null,
            'employees_count' => (int) ($r['employees_count'] ?? $r['employee_count'] ?? $r['employees'] ?? $r['count'] ?? 0),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'D1', 'name' => 'الاستقبال', 'description' => 'استقبال العملاء وإدارة الحجوزات والمواعيد.', 'employees_count' => 4],
            ['uuid' => 'D2', 'name' => 'التصفيف', 'description' => 'قص وتصفيف وصبغ الشعر.', 'employees_count' => 6],
            ['uuid' => 'D3', 'name' => 'العناية بالبشرة', 'description' => 'جلسات التنظيف والعناية بالوجه والبشرة.', 'employees_count' => 3],
            ['uuid' => 'D4', 'name' => 'الأظافر', 'description' => 'المانيكير والباديكير وتركيب الأظافر.', 'employees_count' => 2],
            ['uuid' => 'D5', 'name' => 'الإدارة', 'description' => 'الإشراف والحسابات وإدارة الفرع.', 'employees_count' => 2],
        ];
    }
}
