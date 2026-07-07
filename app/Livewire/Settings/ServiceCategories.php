<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Data\Waqty\ServiceCategoryData;
use App\Services\Waqty\ServiceCatalogService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Service Categories — Waqty')]
class ServiceCategories extends Component
{
    use HandlesWaqtyErrors;

    public bool $showForm = false;

    public ?string $editingUuid = null;

    public string $form_name = '';

    public string $form_color = '#8b5cf6';

    public bool $form_active = true;

    public bool $showDelete = false;

    public ?string $deletingUuid = null;

    /** @var array<int, ServiceCategoryData>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    /** @return array<int, ServiceCategoryData> */
    #[Computed]
    public function items(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $this->loaded = app(ServiceCatalogService::class)->categories();
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = array_map(fn ($a) => ServiceCategoryData::from($a), $this->fallbackData());
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
        $this->form_color = '#8b5cf6';
        $this->form_active = true;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $uuid): void
    {
        $c = collect($this->items())->firstWhere('uuid', $uuid);
        if (! $c) {
            return;
        }
        $this->editingUuid = $uuid;
        $this->form_name = (string) $c->name;
        $this->form_color = $c->color ?? '#8b5cf6';
        $this->form_active = $c->active;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_name' => ['required', 'string', 'max:60'],
            'form_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $payload = [
            'name' => trim($this->form_name),
            'color' => $this->form_color,
            'active' => $this->form_active,
        ];

        $result = $this->waqty(function () use ($payload) {
            $service = app(ServiceCatalogService::class);
            $this->editingUuid
                ? $service->updateCategory($this->editingUuid, $payload)
                : $service->createCategory($payload);

            return true;
        }, __('settings.serviceCategories.createFailed'));

        if ($result || $this->usingFallback()) {
            $this->showForm = false;
            $this->editingUuid = null;
            $this->loaded = null;
            unset($this->items);
            $this->dispatch('notify', type: 'success', message: __('settings.serviceCategories.updated'));
        }
    }

    public function confirmDelete(string $uuid): void
    {
        $this->deletingUuid = $uuid;
        $this->showDelete = true;
    }

    public function deleteCategory(): void
    {
        if (! $this->deletingUuid) {
            return;
        }
        $uuid = $this->deletingUuid;
        $result = $this->waqty(fn () => app(ServiceCatalogService::class)->deleteCategory($uuid) ?? true, __('settings.serviceCategories.deleteFailed'));

        $this->showDelete = false;
        $this->deletingUuid = null;

        if ($result || $this->usingFallback()) {
            $this->loaded = null;
            unset($this->items);
            $this->dispatch('notify', type: 'success', message: __('settings.serviceCategories.deleted'));
        }
    }

    public function render()
    {
        return view('livewire.settings.service-categories');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'CAT1', 'name' => 'تصفيف الشعر', 'color' => '#8b5cf6', 'services_count' => 8, 'active' => true],
            ['uuid' => 'CAT2', 'name' => 'الأظافر', 'color' => '#ec4899', 'services_count' => 5, 'active' => true],
            ['uuid' => 'CAT3', 'name' => 'العناية بالبشرة', 'color' => '#10b981', 'services_count' => 6, 'active' => true],
            ['uuid' => 'CAT4', 'name' => 'مساج', 'color' => '#f59e0b', 'services_count' => 3, 'active' => true],
        ];
    }
}
