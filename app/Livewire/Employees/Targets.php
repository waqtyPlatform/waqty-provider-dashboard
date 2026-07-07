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
#[Title('Targets — Waqty')]
class Targets extends Component
{
    use HandlesWaqtyErrors;

    public bool $showForm = false;

    public ?string $editingUuid = null;

    public string $form_employee = '';

    public string $form_value = '';

    public string $form_type = 'revenue';

    public string $form_tier = '1';

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
            $rows = app(EmployeeHrService::class)->targets();
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $rows = $this->fallbackData();
        }

        $this->loaded = array_map(fn ($r) => $this->normalize($r), $rows);

        return $this->loaded;
    }

    public function usingFallback(): bool
    {
        $this->items();

        return $this->fallbackUsed;
    }

    /** @return array{onTrack:int, achieved:int, bonus:int} */
    #[Computed]
    public function kpis(): array
    {
        $items = $this->items();

        return [
            'onTrack' => count(array_filter($items, fn ($t) => $t['progress'] >= 70 && $t['progress'] < 100)),
            'achieved' => count(array_filter($items, fn ($t) => $t['progress'] >= 100)),
            'bonus' => array_sum(array_map(fn ($t) => $t['progress'] >= 100 ? (int) $t['bonus'] : 0, $items)),
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['editingUuid', 'form_employee', 'form_value']);
        $this->form_type = 'revenue';
        $this->form_tier = '1';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $uuid): void
    {
        $t = collect($this->items())->firstWhere('uuid', $uuid);
        if (! $t) {
            return;
        }
        $this->editingUuid = $uuid;
        $this->form_employee = (string) $t['employee'];
        $this->form_type = (string) $t['type'];
        $this->form_value = (string) ($t['type'] === 'revenue' ? Money::fromMinor((int) $t['target']) : $t['target']);
        $this->form_tier = (string) $t['tier'];
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_employee' => ['required', 'string', 'max:100'],
            'form_type' => ['required', 'in:revenue,bookings'],
            'form_value' => ['required', 'numeric', 'gt:0'],
            'form_tier' => ['required', 'numeric', 'min:1'],
        ]);

        $target = $this->form_type === 'revenue'
            ? Money::toMinor((float) $this->form_value)
            : (int) round((float) $this->form_value);

        $payload = [
            'employee' => trim($this->form_employee),
            'type' => $this->form_type,
            'target' => $target,
            'tier_multiplier' => (float) $this->form_tier,
        ];

        $result = $this->waqty(function () use ($payload) {
            $service = app(EmployeeHrService::class);
            $this->editingUuid
                ? $service->updateTarget($this->editingUuid, $payload)
                : $service->createTarget($payload);

            return true;
        }, __('emp.targets.saveFailed'));

        if ($result || $this->usingFallback()) {
            $this->showForm = false;
            $this->editingUuid = null;
            $this->loaded = null;
            unset($this->items, $this->kpis);
            $this->dispatch('notify', type: 'success', message: __('emp.targets.saved'));
        }
    }

    public function render()
    {
        return view('livewire.employees.targets');
    }

    /**
     * Shape a raw API/sample row into the columns this screen renders,
     * deriving the progress percentage from achieved vs. target.
     *
     * @param  array<string, mixed>  $r
     * @return array<string, mixed>
     */
    private function normalize(array $r): array
    {
        $target = (int) ($r['target'] ?? $r['target_value'] ?? 0);
        $achieved = (int) ($r['achieved'] ?? $r['current'] ?? 0);
        $progress = $target > 0 ? (int) round($achieved / $target * 100) : 0;

        return [
            'uuid' => (string) ($r['uuid'] ?? $r['id'] ?? ''),
            'employee' => (string) ($r['employee'] ?? $r['employee_name'] ?? ''),
            'type' => ($r['type'] ?? 'revenue') === 'bookings' ? 'bookings' : 'revenue',
            'target' => $target,
            'achieved' => $achieved,
            'progress' => $progress,
            'tier' => (float) ($r['tier_multiplier'] ?? $r['tier'] ?? 1),
            'bonus' => (int) ($r['bonus'] ?? 0),
            'period' => (string) ($r['period'] ?? ''),
        ];
    }

    /**
     * Arabic sample targets with varied progress for graceful degradation.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'T1', 'employee' => 'سارة أحمد', 'type' => 'revenue', 'target' => 30000000, 'achieved' => 34500000, 'tier_multiplier' => 1.5, 'bonus' => 500000, 'period' => 'يوليو 2026'],
            ['uuid' => 'T2', 'employee' => 'منى عادل', 'type' => 'revenue', 'target' => 20000000, 'achieved' => 16000000, 'tier_multiplier' => 1.25, 'bonus' => 300000, 'period' => 'يوليو 2026'],
            ['uuid' => 'T3', 'employee' => 'خالد حسن', 'type' => 'bookings', 'target' => 120, 'achieved' => 92, 'tier_multiplier' => 1.25, 'bonus' => 250000, 'period' => 'يوليو 2026'],
            ['uuid' => 'T4', 'employee' => 'ياسمين فاروق', 'type' => 'bookings', 'target' => 100, 'achieved' => 42, 'tier_multiplier' => 1.0, 'bonus' => 150000, 'period' => 'يوليو 2026'],
            ['uuid' => 'T5', 'employee' => 'عمر نبيل', 'type' => 'bookings', 'target' => 80, 'achieved' => 80, 'tier_multiplier' => 1.5, 'bonus' => 400000, 'period' => 'يوليو 2026'],
        ];
    }
}
