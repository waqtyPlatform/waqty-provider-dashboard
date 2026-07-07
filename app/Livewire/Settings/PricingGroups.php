<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Data\Waqty\PricingGroupData;
use App\Services\Waqty\PricingGroupService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Pricing Groups — Waqty')]
class PricingGroups extends Component
{
    use HandlesWaqtyErrors;

    public bool $showForm = false;

    public ?string $editingUuid = null;

    public string $form_name = '';

    public bool $form_active = true;

    public bool $showDelete = false;

    public ?string $deletingUuid = null;

    /** @var array<string, bool> */
    public array $overrides = [];

    /** @var array<int, PricingGroupData>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    /** @return array<int, PricingGroupData> */
    #[Computed]
    public function items(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $this->loaded = app(PricingGroupService::class)->list();
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = array_map(fn ($a) => PricingGroupData::from($a), $this->fallbackData());
        }

        foreach ($this->loaded as $g) {
            if (isset($this->overrides[$g->uuid])) {
                $g->active = $this->overrides[$g->uuid];
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
        $this->form_active = true;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $uuid): void
    {
        $g = collect($this->items())->firstWhere('uuid', $uuid);
        if (! $g) {
            return;
        }
        $this->editingUuid = $uuid;
        $this->form_name = (string) $g->name;
        $this->form_active = $g->active;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_name' => ['required', 'string', 'max:60'],
        ]);

        $payload = [
            'name' => trim($this->form_name),
            'active' => $this->form_active,
        ];

        $result = $this->waqty(function () use ($payload) {
            $service = app(PricingGroupService::class);
            $this->editingUuid
                ? $service->update($this->editingUuid, $payload)
                : $service->create($payload);

            return true;
        }, __('settings.pricingGroups.toastCreateFailed'));

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
        $g = collect($this->items())->firstWhere('uuid', $uuid);
        if (! $g) {
            return;
        }
        $next = ! $g->active;
        $this->overrides[$uuid] = $next;

        if (! $this->usingFallback()) {
            $this->waqty(fn () => app(PricingGroupService::class)->update($uuid, ['active' => $next]) ?? true, __('settings.pricingGroups.toastUpdateFailed'));
        }

        unset($this->items);
    }

    public function confirmDelete(string $uuid): void
    {
        $this->deletingUuid = $uuid;
        $this->showDelete = true;
    }

    public function deleteGroup(): void
    {
        if (! $this->deletingUuid) {
            return;
        }
        $uuid = $this->deletingUuid;
        $result = $this->waqty(fn () => app(PricingGroupService::class)->delete($uuid) ?? true, __('settings.pricingGroups.toastDeleteFailed'));

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
        return view('livewire.settings.pricing-groups');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'PG1', 'name' => 'كبار المصففين', 'active' => true, 'employees_count' => 12],
            ['uuid' => 'PG2', 'name' => 'الطاقم المبتدئ', 'active' => true, 'employees_count' => 8],
            ['uuid' => 'PG3', 'name' => 'المتدربون', 'active' => false, 'employees_count' => 3],
        ];
    }
}
