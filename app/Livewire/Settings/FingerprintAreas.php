<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Data\Waqty\FingerprintAreaData;
use App\Services\Waqty\FingerprintAreaService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Fingerprint Areas — Waqty')]
class FingerprintAreas extends Component
{
    use HandlesWaqtyErrors;

    public bool $showForm = false;

    public ?string $editingUuid = null;

    public string $form_name = '';

    public string $form_device = '';

    public bool $form_active = true;

    public bool $showDelete = false;

    public ?string $deletingUuid = null;

    /** @var array<string, bool> */
    public array $overrides = [];

    /** @var array<int, FingerprintAreaData>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    /** @return array<int, FingerprintAreaData> */
    #[Computed]
    public function items(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $this->loaded = app(FingerprintAreaService::class)->list();
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = array_map(fn ($a) => FingerprintAreaData::from($a), $this->fallbackData());
        }

        foreach ($this->loaded as $a) {
            if (isset($this->overrides[$a->uuid])) {
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
        $this->reset(['editingUuid', 'form_name', 'form_device']);
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
        $this->form_device = (string) $a->device;
        $this->form_active = $a->active;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_name' => ['required', 'string', 'max:60'],
            'form_device' => ['nullable', 'string', 'max:60'],
        ]);

        $payload = [
            'name' => trim($this->form_name),
            'device' => trim($this->form_device),
            'active' => $this->form_active,
        ];

        $result = $this->waqty(function () use ($payload) {
            $service = app(FingerprintAreaService::class);
            $this->editingUuid
                ? $service->update($this->editingUuid, $payload)
                : $service->create($payload);

            return true;
        }, __('settings.fpAreas.createFailed'));

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
        $a = collect($this->items())->firstWhere('uuid', $uuid);
        if (! $a) {
            return;
        }
        $next = ! $a->active;
        $this->overrides[$uuid] = $next;

        if (! $this->usingFallback()) {
            $this->waqty(fn () => app(FingerprintAreaService::class)->update($uuid, ['active' => $next]) ?? true, __('settings.fpAreas.updateFailed'));
        }

        unset($this->items);
    }

    public function confirmDelete(string $uuid): void
    {
        $this->deletingUuid = $uuid;
        $this->showDelete = true;
    }

    public function deleteArea(): void
    {
        if (! $this->deletingUuid) {
            return;
        }
        $uuid = $this->deletingUuid;
        $result = $this->waqty(fn () => app(FingerprintAreaService::class)->delete($uuid) ?? true, __('settings.fpAreas.deleteFailed'));

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
        return view('livewire.settings.fingerprint-areas');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'FA1', 'name' => 'الاستقبال الرئيسي', 'device' => 'جهاز الباب الأمامي ZKTeco', 'active' => true],
            ['uuid' => 'FA2', 'name' => 'غرفة الموظفين', 'device' => 'مدخل الموظفين', 'active' => true],
            ['uuid' => 'FA3', 'name' => 'المستودع', 'device' => null, 'active' => false],
        ];
    }
}
