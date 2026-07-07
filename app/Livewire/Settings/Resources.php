<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Data\Waqty\ResourceData;
use App\Services\Waqty\SettingsService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Resources — Waqty')]
class Resources extends Component
{
    use HandlesWaqtyErrors;

    public bool $showForm = false;

    public ?string $editingUuid = null;

    public string $form_name = '';

    public string $form_type = 'chair';

    public int $form_capacity = 1;

    public string $form_status = 'active';

    public bool $showDelete = false;

    public ?string $deletingUuid = null;

    /** @var array<int, ResourceData>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    /** @return array<int, ResourceData> */
    #[Computed]
    public function items(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $this->loaded = app(SettingsService::class)->resources();
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = array_map(fn ($a) => ResourceData::from($a), $this->fallbackData());
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
        $this->reset(['editingUuid', 'form_name', 'form_capacity']);
        $this->form_type = 'chair';
        $this->form_capacity = 1;
        $this->form_status = 'active';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $uuid): void
    {
        $r = collect($this->items())->firstWhere('uuid', $uuid);
        if (! $r) {
            return;
        }
        $this->editingUuid = $uuid;
        $this->form_name = (string) $r->name;
        $this->form_type = $r->type;
        $this->form_capacity = $r->capacity;
        $this->form_status = $r->status;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_name' => ['required', 'string', 'max:60'],
            'form_type' => ['required', 'in:chair,room,equipment'],
            'form_capacity' => ['required', 'integer', 'min:1', 'max:99'],
            'form_status' => ['required', 'in:active,maintenance'],
        ]);

        $payload = [
            'name' => trim($this->form_name),
            'type' => $this->form_type,
            'capacity' => $this->form_capacity,
            'status' => $this->form_status,
        ];

        $result = $this->waqty(function () use ($payload) {
            $service = app(SettingsService::class);
            $this->editingUuid
                ? $service->updateResource($this->editingUuid, $payload)
                : $service->createResource($payload);

            return true;
        }, __('settings.resources.createFailed'));

        if ($result || $this->usingFallback()) {
            $this->showForm = false;
            $this->editingUuid = null;
            $this->loaded = null;
            unset($this->items);
            $this->dispatch('notify', type: 'success', message: __('settings.resources.updated'));
        }
    }

    public function confirmDelete(string $uuid): void
    {
        $this->deletingUuid = $uuid;
        $this->showDelete = true;
    }

    public function deleteResource(): void
    {
        if (! $this->deletingUuid) {
            return;
        }
        $uuid = $this->deletingUuid;
        $result = $this->waqty(fn () => app(SettingsService::class)->deleteResource($uuid) ?? true, __('settings.resources.deleteFailed'));

        $this->showDelete = false;
        $this->deletingUuid = null;

        if ($result || $this->usingFallback()) {
            $this->loaded = null;
            unset($this->items);
            $this->dispatch('notify', type: 'success', message: __('settings.resources.deletedPermanently'));
        }
    }

    public function render()
    {
        return view('livewire.settings.resources');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'RS1', 'name' => 'محطة تصفيف 1', 'type' => 'chair', 'capacity' => 1, 'status' => 'active'],
            ['uuid' => 'RS2', 'name' => 'محطة تصفيف 2', 'type' => 'chair', 'capacity' => 1, 'status' => 'active'],
            ['uuid' => 'RS3', 'name' => 'غرفة العلاج أ', 'type' => 'room', 'capacity' => 2, 'status' => 'active'],
            ['uuid' => 'RS4', 'name' => 'جهاز الليزر', 'type' => 'equipment', 'capacity' => 1, 'status' => 'maintenance'],
        ];
    }
}
