<?php

declare(strict_types=1);

namespace App\Livewire\Employees;

use App\Services\Waqty\EmployeeHrService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use App\Support\Money;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Positions — Waqty')]
class Positions extends Component
{
    use HandlesWaqtyErrors;

    public bool $showForm = false;

    public ?string $editingUuid = null;

    public string $form_title = '';

    public string $form_department = '';

    public string $form_level = 'junior';

    public string $form_salary_min = '0';

    public string $form_salary_max = '0';

    public bool $showDelete = false;

    public ?string $deletingUuid = null;

    /** @var array<int, array<string, mixed>>|null per-request memo */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    /** @return array<int, array<string, mixed>> */
    #[Computed]
    public function items(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $rows = app(EmployeeHrService::class)->positions();
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $rows = $this->fallbackData();
        }

        $this->loaded = array_map(fn ($r) => $this->normalize((array) $r), $rows);

        return $this->loaded;
    }

    public function usingFallback(): bool
    {
        $this->items();

        return $this->fallbackUsed;
    }

    /** @return array<string, string> department name => label for the <select> */
    #[Computed]
    public function departmentOptions(): array
    {
        $options = [];
        foreach ($this->items() as $p) {
            $dept = (string) $p['department'];
            if ($dept !== '') {
                $options[$dept] = $dept;
            }
        }

        return $options;
    }

    public function openCreate(): void
    {
        $this->reset(['editingUuid', 'form_title', 'form_department']);
        $this->form_level = 'junior';
        $this->form_salary_min = '0';
        $this->form_salary_max = '0';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $uuid): void
    {
        $p = collect($this->items())->firstWhere('uuid', $uuid);
        if (! $p) {
            return;
        }
        $this->editingUuid = $uuid;
        $this->form_title = (string) $p['title'];
        $this->form_department = (string) $p['department'];
        $this->form_level = (string) $p['level'];
        $this->form_salary_min = (string) Money::fromMinor((int) $p['salary_min']);
        $this->form_salary_max = (string) Money::fromMinor((int) $p['salary_max']);
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_title' => ['required', 'string', 'max:80'],
            'form_department' => ['nullable', 'string', 'max:80'],
            'form_level' => ['required', 'in:junior,mid,senior'],
            'form_salary_min' => ['required', 'numeric', 'min:0'],
            'form_salary_max' => ['required', 'numeric', 'min:0', 'gte:form_salary_min'],
        ], [
            'form_salary_max.gte' => __('emp.positions.errSalaryRange'),
        ]);

        $payload = [
            'title' => trim($this->form_title),
            'department' => trim($this->form_department) ?: null,
            'level' => $this->form_level,
            'salary_min' => Money::toMinor((float) $this->form_salary_min),
            'salary_max' => Money::toMinor((float) $this->form_salary_max),
        ];

        $result = $this->waqty(function () use ($payload) {
            $service = app(EmployeeHrService::class);
            $this->editingUuid
                ? $service->updatePosition($this->editingUuid, $payload)
                : $service->createPosition($payload);

            return true;
        }, __('emp.positions.saveFailed'));

        if ($result || $this->usingFallback()) {
            $this->showForm = false;
            $this->editingUuid = null;
            $this->loaded = null;
            unset($this->items);
            $this->dispatch('notify', type: 'success', message: __('common.saved'));
        }
    }

    public function confirmDelete(string $uuid): void
    {
        $this->deletingUuid = $uuid;
        $this->showDelete = true;
    }

    public function deletePosition(): void
    {
        if (! $this->deletingUuid) {
            return;
        }
        $uuid = $this->deletingUuid;
        $result = $this->waqty(fn () => app(EmployeeHrService::class)->deletePosition($uuid) ?? true, __('emp.positions.deleteFailed'));

        $this->showDelete = false;
        $this->deletingUuid = null;

        if ($result || $this->usingFallback()) {
            $this->loaded = null;
            unset($this->items);
            $this->dispatch('notify', type: 'success', message: __('common.deleted'));
        }
    }

    public function render()
    {
        return view('livewire.employees.positions');
    }

    /**
     * Shape a raw API/sample row into the columns this screen renders.
     *
     * @param  array<string, mixed>  $r
     * @return array<string, mixed>
     */
    private function normalize(array $r): array
    {
        return [
            'uuid' => (string) ($r['uuid'] ?? ''),
            'title' => (string) ($r['title'] ?? ''),
            'department' => (string) ($r['department'] ?? ''),
            'level' => in_array($r['level'] ?? null, ['junior', 'mid', 'senior'], true) ? (string) $r['level'] : 'junior',
            'salary_min' => (int) ($r['salary_min'] ?? 0),
            'salary_max' => (int) ($r['salary_max'] ?? 0),
        ];
    }

    /** Sample positions (Arabic) shown when the API is unavailable. @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'P1', 'title' => 'مصفف شعر أول', 'department' => 'التصفيف', 'level' => 'senior', 'salary_min' => 400000, 'salary_max' => 800000],
            ['uuid' => 'P2', 'title' => 'أخصائي عناية بالبشرة', 'department' => 'العناية بالبشرة', 'level' => 'mid', 'salary_min' => 350000, 'salary_max' => 600000],
            ['uuid' => 'P3', 'title' => 'فني أظافر', 'department' => 'العناية بالأظافر', 'level' => 'junior', 'salary_min' => 250000, 'salary_max' => 400000],
            ['uuid' => 'P4', 'title' => 'موظف استقبال', 'department' => 'الاستقبال', 'level' => 'junior', 'salary_min' => 300000, 'salary_max' => 450000],
            ['uuid' => 'P5', 'title' => 'مدير فرع', 'department' => 'الإدارة', 'level' => 'senior', 'salary_min' => 800000, 'salary_max' => 1500000],
        ];
    }
}
