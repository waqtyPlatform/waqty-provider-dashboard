<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Data\Waqty\ShiftAutomationData;
use App\Services\Waqty\ShiftAutomationService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Shift Automations — Waqty')]
class ShiftAutomations extends Component
{
    use HandlesWaqtyErrors;

    public bool $showForm = false;

    public ?string $editingUuid = null;

    public string $form_name = '';

    public string $form_trigger = 'shift_start';

    public string $form_action = 'notify_manager';

    public bool $form_active = true;

    public bool $showDelete = false;

    public ?string $deletingUuid = null;

    /** @var array<string, bool> */
    public array $overrides = [];

    /** @var array<int, ShiftAutomationData>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    /** @return array<int, ShiftAutomationData> */
    #[Computed]
    public function items(): array
    {
        if ($this->loaded === null) {
            try {
                $this->loaded = app(ShiftAutomationService::class)->list();
            } catch (WaqtyApiException) {
                $this->fallbackUsed = true;
                $this->loaded = array_map(fn ($a) => ShiftAutomationData::from($a), $this->fallbackData());
            }
        }

        foreach ($this->loaded as $a) {
            if ($a->uuid !== null && isset($this->overrides[$a->uuid])) {
                $a->active = $this->overrides[$a->uuid];
            }
        }

        return $this->loaded;
    }

    public function usingFallback(): bool
    {
        $this->items();

        return $this->fallbackUsed;
    }

    public function openCreate(): void
    {
        $this->reset(['editingUuid', 'form_name']);
        $this->form_trigger = 'shift_start';
        $this->form_action = 'notify_manager';
        $this->form_active = true;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $uuid): void
    {
        $a = collect($this->items())->firstWhere('uuid', $uuid);
        if (! $a) {
            return;
        }
        $this->editingUuid = $uuid;
        $this->form_name = (string) $a->name;
        $this->form_trigger = $a->trigger;
        $this->form_action = $a->action;
        $this->form_active = $a->active;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_name' => ['required', 'string', 'max:60'],
            'form_trigger' => ['required', 'in:shift_start,shift_end,late_checkin,missed_shift'],
            'form_action' => ['required', 'in:notify_manager,auto_clock_out,send_reminder'],
        ]);

        $payload = [
            'name' => trim($this->form_name),
            'trigger' => $this->form_trigger,
            'action' => $this->form_action,
            'active' => $this->form_active,
        ];

        $result = $this->waqty(function () use ($payload) {
            $service = app(ShiftAutomationService::class);
            $this->editingUuid
                ? $service->update($this->editingUuid, $payload)
                : $service->create($payload);

            return true;
        }, __('settings.shiftAutomations.createFailed'));

        if ($result || $this->usingFallback()) {
            $this->showForm = false;
            $this->editingUuid = null;
            $this->loaded = null;
            unset($this->items);
            $this->dispatch('notify', type: 'success', message: __('settings.saved'));
        }
    }

    public function toggleActive(string $uuid): void
    {
        $a = collect($this->items())->firstWhere('uuid', $uuid);
        if (! $a) {
            return;
        }
        $next = ! $a->active;
        $this->overrides[$uuid] = $next;

        if (! $this->usingFallback()) {
            $this->waqty(fn () => app(ShiftAutomationService::class)->update($uuid, ['active' => $next]) ?? true, __('settings.shiftAutomations.updateFailed'));
        }

        unset($this->items);
    }

    public function confirmDelete(string $uuid): void
    {
        $this->deletingUuid = $uuid;
        $this->showDelete = true;
    }

    public function deleteAutomation(): void
    {
        if (! $this->deletingUuid) {
            return;
        }
        $uuid = $this->deletingUuid;
        $result = $this->waqty(fn () => app(ShiftAutomationService::class)->delete($uuid) ?? true, __('settings.shiftAutomations.deleteFailed'));

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
        return view('livewire.settings.shift-automations');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'SA1', 'name' => 'تنبيه التأخير', 'trigger' => 'late_checkin', 'action' => 'notify_manager', 'active' => true],
            ['uuid' => 'SA2', 'name' => 'تسجيل خروج تلقائي', 'trigger' => 'shift_end', 'action' => 'auto_clock_out', 'active' => true],
            ['uuid' => 'SA3', 'name' => 'تذكير بالوردية', 'trigger' => 'shift_start', 'action' => 'send_reminder', 'active' => false],
        ];
    }
}
