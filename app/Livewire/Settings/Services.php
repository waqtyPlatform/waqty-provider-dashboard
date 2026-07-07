<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Data\Waqty\ServiceData;
use App\Services\Waqty\ServiceCatalogService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use App\Support\Money;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Settings › Services — catalog management (table view).
 * Sibling of the POS-facing Services\Index card grid; both are backed by
 * ServiceCatalogService (/api/provider/services, multipart create/update).
 */
#[Layout('components.layouts.app')]
#[Title('Services — Waqty')]
class Services extends Component
{
    use HandlesWaqtyErrors;
    use WithFileUploads;

    public string $search = '';

    public string $categoryFilter = 'all';

    public bool $showForm = false;

    public ?string $editingUuid = null;

    public string $form_name = '';

    public string $form_name_ar = '';

    public string $form_category = '';

    public int $form_duration = 30;

    public string $form_price = '';

    public string $form_description = '';

    public bool $form_active = true;

    public $form_image = null;

    public bool $showDelete = false;

    public ?string $deletingUuid = null;

    /** Optimistic active overrides. @var array<string, bool> */
    public array $overrides = [];

    /** @var array<int, ServiceData>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    /** @return array<int, ServiceData> */
    private function source(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $this->loaded = app(ServiceCatalogService::class)->services();
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = array_map(fn ($a) => ServiceData::from($a), $this->fallbackData());
        }

        foreach ($this->loaded as $svc) {
            if (isset($this->overrides[$svc->uuid])) {
                $svc->active = $this->overrides[$svc->uuid];
            }
        }

        return $this->loaded;
    }

    public function usingFallback(): bool
    {
        $this->source();

        return $this->fallbackUsed;
    }

    /** @return array<int, ServiceData> */
    #[Computed]
    public function filtered(): array
    {
        $search = trim(mb_strtolower($this->search));
        $category = $this->categoryFilter;

        return array_values(array_filter($this->source(), function (ServiceData $s) use ($search, $category) {
            $matchesSearch = $search === ''
                || str_contains(mb_strtolower((string) $s->name), $search)
                || str_contains(mb_strtolower((string) $s->name_ar), $search);

            $matchesCategory = $category === 'all' || $s->categoryName() === $category;

            return $matchesSearch && $matchesCategory;
        }));
    }

    /** Distinct category names present in the current list. @return array<int, string> */
    #[Computed]
    public function categories(): array
    {
        $names = array_filter(array_map(fn (ServiceData $s) => $s->categoryName(), $this->source()));

        return array_values(array_unique($names));
    }

    public function openCreate(): void
    {
        $this->reset(['editingUuid', 'form_name', 'form_name_ar', 'form_category', 'form_price', 'form_description', 'form_image']);
        $this->form_duration = 30;
        $this->form_active = true;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $uuid): void
    {
        $service = collect($this->source())->firstWhere('uuid', $uuid);
        if (! $service) {
            return;
        }

        $this->editingUuid = $uuid;
        $this->form_name = (string) $service->name;
        $this->form_name_ar = (string) $service->name_ar;
        $this->form_category = (string) $service->categoryName();
        $this->form_duration = $service->estimated_duration_minutes ?? 30;
        $this->form_price = $service->price ? (string) Money::fromMinor($service->price) : '';
        $this->form_description = (string) $service->description;
        $this->form_active = $service->active;
        $this->form_image = null;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_name' => ['required', 'string', 'max:100'],
            'form_name_ar' => ['nullable', 'string', 'max:100'],
            'form_category' => ['nullable', 'string', 'max:100'],
            'form_duration' => ['required', 'integer', 'min:5', 'max:480'],
            'form_price' => ['nullable', 'numeric', 'min:0'],
            'form_description' => ['nullable', 'string', 'max:500'],
            'form_image' => ['nullable', 'image', 'max:2048'],
        ], ['form_name.required' => __('settings.services.nameRequired')]);

        $fields = array_filter([
            'name' => trim($this->form_name),
            'name_ar' => trim($this->form_name_ar) ?: null,
            'category' => trim($this->form_category) ?: null,
            'estimated_duration_minutes' => $this->form_duration,
            'base_price' => $this->form_price !== '' ? Money::toMinor((float) $this->form_price) : null,
            'description' => trim($this->form_description) ?: null,
            'active' => $this->form_active ? '1' : '0',
        ], fn ($v) => $v !== null);

        $files = $this->form_image ? ['image' => $this->form_image] : [];

        $editing = $this->editingUuid;

        $result = $this->waqty(function () use ($fields, $files, $editing) {
            $service = app(ServiceCatalogService::class);
            $editing
                ? $service->updateService($editing, $fields, $files)
                : $service->createService($fields, $files);

            return true;
        }, __('settings.services.deleteFailed'));

        if ($result || $this->usingFallback()) {
            $this->showForm = false;
            $this->editingUuid = null;
            $this->loaded = null;
            unset($this->filtered, $this->categories);
            $this->dispatch('notify', type: 'success', message: $editing ? __('settings.services.updated') : __('settings.services.added'));
        }
    }

    public function toggleActive(string $uuid): void
    {
        $svc = collect($this->source())->firstWhere('uuid', $uuid);
        if (! $svc) {
            return;
        }
        $next = ! $svc->active;
        $this->overrides[$uuid] = $next;

        if (! $this->usingFallback()) {
            $this->waqty(fn () => app(ServiceCatalogService::class)->toggleActive($uuid, $next) ?? true, __('waqty.genericError'));
        }

        unset($this->filtered);
    }

    public function confirmDelete(string $uuid): void
    {
        $this->deletingUuid = $uuid;
        $this->showDelete = true;
    }

    public function deleteService(): void
    {
        if (! $this->deletingUuid) {
            return;
        }
        $uuid = $this->deletingUuid;

        $result = $this->waqty(fn () => app(ServiceCatalogService::class)->deleteService($uuid) ?? true, __('settings.services.deleteFailed'));

        $this->showDelete = false;
        $this->deletingUuid = null;

        if ($result || $this->usingFallback()) {
            $this->loaded = null;
            unset($this->filtered, $this->categories);
            $this->dispatch('notify', type: 'success', message: __('settings.services.removed'));
        }
    }

    public function render()
    {
        return view('livewire.settings.services');
    }

    /** Sample catalog mirroring the source fallback for graceful degradation. */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'S001', 'name' => 'قصّة شعر كلاسيك', 'name_ar' => 'قصّة شعر كلاسيك', 'sub_category' => ['name' => 'الشعر'], 'estimated_duration_minutes' => 30, 'active' => true, 'price' => 15000, 'description' => 'غسيل وقص وتصفيف.'],
            ['uuid' => 'S002', 'name' => 'تهذيب اللحية', 'name_ar' => 'تهذيب اللحية', 'sub_category' => ['name' => 'الشعر'], 'estimated_duration_minutes' => 20, 'active' => true, 'price' => 8000],
            ['uuid' => 'S003', 'name' => 'صبغة شعر', 'name_ar' => 'صبغة شعر', 'sub_category' => ['name' => 'الصبغة'], 'estimated_duration_minutes' => 90, 'active' => true, 'price' => 45000, 'description' => 'صبغة كاملة بمنتجات فاخرة.'],
            ['uuid' => 'S004', 'name' => 'مانيكير', 'name_ar' => 'مانيكير', 'sub_category' => ['name' => 'الأظافر'], 'estimated_duration_minutes' => 45, 'active' => true, 'price' => 20000],
            ['uuid' => 'S005', 'name' => 'مساج الأنسجة العميقة', 'name_ar' => 'مساج الأنسجة العميقة', 'sub_category' => ['name' => 'سبا'], 'estimated_duration_minutes' => 60, 'active' => true, 'price' => 55000, 'description' => 'مساج علاجي لكامل الجسم.'],
            ['uuid' => 'S006', 'name' => 'جلسة عناية بالبشرة', 'name_ar' => 'جلسة عناية بالبشرة', 'sub_category' => ['name' => 'البشرة'], 'estimated_duration_minutes' => 60, 'active' => false, 'price' => 40000],
        ];
    }
}
