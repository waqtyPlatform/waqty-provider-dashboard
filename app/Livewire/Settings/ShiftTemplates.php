<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Data\Waqty\ShiftTemplateData;
use App\Services\Waqty\ShiftTemplateService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Shift Templates — Waqty')]
class ShiftTemplates extends Component
{
    use HandlesWaqtyErrors;

    public bool $showForm = false;

    public ?string $editingUuid = null;

    public string $form_name = '';

    public string $form_start_time = '09:00';

    public string $form_end_time = '17:00';

    public bool $form_active = true;

    public bool $showDelete = false;

    public ?string $deletingUuid = null;

    /** @var array<string, bool> */
    public array $overrides = [];

    /** @var array<int, ShiftTemplateData>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    /** @return array<int, ShiftTemplateData> */
    #[Computed]
    public function items(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $this->loaded = app(ShiftTemplateService::class)->list();
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = array_map(fn ($a) => ShiftTemplateData::from($a), $this->fallbackData());
        }

        foreach ($this->loaded as $t) {
            if (isset($this->overrides[$t->uuid])) {
                $t->active = $this->overrides[$t->uuid];
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
        $this->form_start_time = '09:00';
        $this->form_end_time = '17:00';
        $this->form_active = true;
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
        $this->form_name = (string) $t->name;
        $this->form_start_time = (string) $t->start_time;
        $this->form_end_time = (string) $t->end_time;
        $this->form_active = $t->active;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_name' => ['required', 'string', 'max:60'],
            'form_start_time' => ['required', 'string'],
            'form_end_time' => ['required', 'string'],
        ]);

        $payload = [
            'name' => trim($this->form_name),
            'start_time' => $this->form_start_time,
            'end_time' => $this->form_end_time,
            'active' => $this->form_active,
        ];

        $result = $this->waqty(function () use ($payload) {
            $service = app(ShiftTemplateService::class);
            $this->editingUuid
                ? $service->update($this->editingUuid, $payload)
                : $service->create($payload);

            return true;
        }, __('settings.shiftTemplates.createFailed'));

        if ($result || $this->usingFallback()) {
            $this->showForm = false;
            $this->editingUuid = null;
            $this->loaded = null;
            unset($this->items);
            $this->dispatch('notify', type: 'success', message: __('common.saved'));
        }
    }

    public function toggleActive(string $uuid): void
    {
        $t = collect($this->items())->firstWhere('uuid', $uuid);
        if (! $t) {
            return;
        }
        $next = ! $t->active;
        $this->overrides[$uuid] = $next;

        if (! $this->usingFallback()) {
            $this->waqty(fn () => app(ShiftTemplateService::class)->update($uuid, ['active' => $next]) ?? true, __('settings.shiftTemplates.updateFailed'));
        }

        unset($this->items);
    }

    public function confirmDelete(string $uuid): void
    {
        $this->deletingUuid = $uuid;
        $this->showDelete = true;
    }

    public function deleteTemplate(): void
    {
        if (! $this->deletingUuid) {
            return;
        }
        $uuid = $this->deletingUuid;
        $result = $this->waqty(fn () => app(ShiftTemplateService::class)->delete($uuid) ?? true, __('settings.shiftTemplates.deleteFailed'));

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
        return view('livewire.settings.shifts');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'ST1', 'name' => 'صباحية', 'start_time' => '09:00', 'end_time' => '17:00', 'active' => true],
            ['uuid' => 'ST2', 'name' => 'مسائية', 'start_time' => '14:00', 'end_time' => '22:00', 'active' => true],
            ['uuid' => 'ST3', 'name' => 'نهاية الأسبوع', 'start_time' => '10:00', 'end_time' => '18:00', 'active' => false],
        ];
    }
}
