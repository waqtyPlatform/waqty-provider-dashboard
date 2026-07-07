<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Data\Waqty\BranchData;
use App\Services\Waqty\BranchSettingsService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Branches — Waqty')]
class Branches extends Component
{
    use HandlesWaqtyErrors;

    public bool $showForm = false;

    public ?string $editingUuid = null;

    public string $form_name = '';

    public string $form_phone = '';

    public string $form_city = '';

    public bool $form_active = true;

    public bool $showDelete = false;

    public ?string $deletingUuid = null;

    /** @var array<string, bool> */
    public array $overrides = [];

    /** @var array<int, BranchData>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    /** @return array<int, BranchData> */
    #[Computed]
    public function items(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $this->loaded = app(BranchSettingsService::class)->list();
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = array_map(fn ($a) => BranchData::from($a), $this->fallbackData());
        }

        foreach ($this->loaded as $b) {
            if (isset($this->overrides[$b->uuid])) {
                $b->active = $this->overrides[$b->uuid];
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
        $this->reset(['editingUuid', 'form_name', 'form_phone', 'form_city']);
        $this->form_active = true;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $uuid): void
    {
        $b = collect($this->items())->firstWhere('uuid', $uuid);
        if (! $b) {
            return;
        }
        $this->editingUuid = $uuid;
        $this->form_name = (string) $b->name;
        $this->form_phone = (string) $b->phone;
        $this->form_city = (string) $b->city;
        $this->form_active = $b->active;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_name' => ['required', 'string', 'max:100'],
            'form_phone' => ['nullable', 'string', 'max:30'],
            'form_city' => ['nullable', 'string', 'max:100'],
        ]);

        $payload = [
            'name' => trim($this->form_name),
            'phone' => trim($this->form_phone),
            'city' => trim($this->form_city),
            'active' => $this->form_active,
        ];

        $result = $this->waqty(function () use ($payload) {
            $service = app(BranchSettingsService::class);
            $this->editingUuid
                ? $service->update($this->editingUuid, $payload)
                : $service->create($payload);

            return true;
        }, __('settings.branches.createFailed'));

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
        $b = collect($this->items())->firstWhere('uuid', $uuid);
        if (! $b) {
            return;
        }
        $next = ! $b->active;
        $this->overrides[$uuid] = $next;

        if (! $this->usingFallback()) {
            $this->waqty(fn () => app(BranchSettingsService::class)->update($uuid, ['active' => $next]) ?? true, __('settings.branches.statusUpdateFailed'));
        }

        unset($this->items);
    }

    public function confirmDelete(string $uuid): void
    {
        $this->deletingUuid = $uuid;
        $this->showDelete = true;
    }

    public function deleteBranch(): void
    {
        if (! $this->deletingUuid) {
            return;
        }
        $uuid = $this->deletingUuid;
        $result = $this->waqty(fn () => app(BranchSettingsService::class)->delete($uuid) ?? true, __('settings.branches.deleteFailed'));

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
        return view('livewire.settings.branches');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'BR1', 'name' => 'فرع وسط البلد', 'phone' => '011 2345 6789', 'city' => 'القاهرة', 'active' => true, 'is_main' => true],
            ['uuid' => 'BR2', 'name' => 'مول العرب', 'phone' => '012 3456 7890', 'city' => 'الجيزة', 'active' => true, 'is_main' => false],
            ['uuid' => 'BR3', 'name' => 'القاهرة الجديدة', 'phone' => '015 4567 8901', 'city' => 'القاهرة الجديدة', 'active' => false, 'is_main' => false],
        ];
    }
}
