<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Data\Waqty\PettyCashItemData;
use App\Services\Waqty\SettingsService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use App\Support\Money;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Petty Cash Items — Waqty')]
class PettyCashItems extends Component
{
    use HandlesWaqtyErrors;

    public bool $showForm = false;

    public ?string $editingUuid = null;

    public string $form_name = '';

    public string $form_category = 'administrative';

    public string $form_limit = '0';

    public bool $form_active = true;

    public bool $showDelete = false;

    public ?string $deletingUuid = null;

    /** @var array<string, bool> */
    public array $overrides = [];

    /** @var array<int, PettyCashItemData>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    /** @return array<int, PettyCashItemData> */
    #[Computed]
    public function items(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $this->loaded = app(SettingsService::class)->pettyCashItems();
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = array_map(fn ($a) => PettyCashItemData::from($a), $this->fallbackData());
        }

        foreach ($this->loaded as $i) {
            if (isset($this->overrides[$i->uuid])) {
                $i->active = $this->overrides[$i->uuid];
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
        $this->reset(['editingUuid', 'form_name', 'form_limit']);
        $this->form_category = 'administrative';
        $this->form_active = true;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $uuid): void
    {
        $item = collect($this->items())->firstWhere('uuid', $uuid);
        if (! $item) {
            return;
        }
        $this->editingUuid = $uuid;
        $this->form_name = (string) $item->name;
        $this->form_category = $item->category;
        $this->form_limit = (string) Money::fromMinor($item->default_amount);
        $this->form_active = $item->active;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_name' => ['required', 'string', 'max:60'],
            'form_category' => ['required', 'in:administrative,kitchen,maintenance,transportation'],
            'form_limit' => ['required', 'numeric', 'min:0'],
        ]);

        $payload = [
            'name' => trim($this->form_name),
            'category' => $this->form_category,
            'default_amount' => Money::toMinor((float) $this->form_limit),
            'active' => $this->form_active,
        ];

        $result = $this->waqty(function () use ($payload) {
            $service = app(SettingsService::class);
            $this->editingUuid
                ? $service->updatePettyCashItem($this->editingUuid, $payload)
                : $service->createPettyCashItem($payload);

            return true;
        }, __('waqty.genericError'));

        if ($result || $this->usingFallback()) {
            $this->showForm = false;
            $this->editingUuid = null;
            $this->loaded = null;
            unset($this->items);
            $this->dispatch('notify', type: 'success', message: __('settings.petty.toastAdded'));
        }
    }

    public function toggleActive(string $uuid): void
    {
        $item = collect($this->items())->firstWhere('uuid', $uuid);
        if (! $item) {
            return;
        }
        $next = ! $item->active;
        $this->overrides[$uuid] = $next;

        if (! $this->usingFallback()) {
            $this->waqty(fn () => app(SettingsService::class)->updatePettyCashItem($uuid, ['active' => $next]) ?? true, __('waqty.genericError'));
        }

        unset($this->items);
    }

    public function confirmDelete(string $uuid): void
    {
        $this->deletingUuid = $uuid;
        $this->showDelete = true;
    }

    public function deleteItem(): void
    {
        if (! $this->deletingUuid) {
            return;
        }
        $uuid = $this->deletingUuid;
        $result = $this->waqty(fn () => app(SettingsService::class)->deletePettyCashItem($uuid) ?? true, __('waqty.genericError'));

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
        return view('livewire.settings.petty-cash-items');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'PC1', 'name' => 'مستلزمات مكتبية', 'category' => 'administrative', 'default_amount' => 50000, 'active' => true],
            ['uuid' => 'PC2', 'name' => 'قهوة ومرطبات', 'category' => 'kitchen', 'default_amount' => 30000, 'active' => true],
            ['uuid' => 'PC3', 'name' => 'إصلاح المعدات', 'category' => 'maintenance', 'default_amount' => 100000, 'active' => true],
            ['uuid' => 'PC4', 'name' => 'مواصلات الموظفين', 'category' => 'transportation', 'default_amount' => 20000, 'active' => false],
        ];
    }
}
