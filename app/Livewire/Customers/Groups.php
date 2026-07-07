<?php

declare(strict_types=1);

namespace App\Livewire\Customers;

use App\Data\Waqty\CustomerGroupData;
use App\Services\Waqty\CustomerService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Client Groups — Waqty')]
class Groups extends Component
{
    use HandlesWaqtyErrors;

    public bool $showForm = false;

    public ?string $editingUuid = null;

    public string $form_name = '';

    public string $form_discount = '0';

    public string $form_color = '#00b166';

    public string $form_description = '';

    public bool $showDelete = false;

    public ?string $deletingUuid = null;

    /** @var array<int, CustomerGroupData>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    /** @return array<int, CustomerGroupData> */
    #[Computed]
    public function groups(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $this->loaded = app(CustomerService::class)->groups();
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = array_map(fn ($a) => CustomerGroupData::from($a), $this->fallbackData());
        }

        return $this->loaded;
    }

    public function usingFallback(): bool
    {
        $this->groups();

        return $this->fallbackUsed;
    }

    public function openCreate(): void
    {
        $this->reset(['editingUuid', 'form_name', 'form_description']);
        $this->form_discount = '0';
        $this->form_color = '#00b166';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $uuid): void
    {
        $group = collect($this->groups())->firstWhere('uuid', $uuid);
        if (! $group) {
            return;
        }

        $this->editingUuid = $uuid;
        $this->form_name = (string) $group->name;
        $this->form_discount = (string) ($group->discount_percentage ?? 0);
        $this->form_color = $group->color ?: '#00b166';
        $this->form_description = (string) $group->description;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_name' => ['required', 'string', 'max:60'],
            'form_discount' => ['required', 'numeric', 'min:0', 'max:100'],
            'form_color' => ['required', 'string'],
            'form_description' => ['nullable', 'string', 'max:255'],
        ]);

        $payload = [
            'name' => trim($this->form_name),
            'discount_percentage' => (float) $this->form_discount,
            'color' => $this->form_color,
            'description' => trim($this->form_description) ?: null,
        ];

        $service = app(CustomerService::class);

        $result = $this->waqty(function () use ($service, $payload) {
            $this->editingUuid
                ? $service->updateGroup($this->editingUuid, $payload)
                : $service->createGroup($payload);

            return true;
        }, __('waqty.genericError'));

        if ($result) {
            $this->showForm = false;
            $this->editingUuid = null;
            $this->loaded = null;
            unset($this->groups);
            $this->dispatch('notify', type: 'success', message: __('common.saved'));
        }
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
        $result = $this->waqty(fn () => app(CustomerService::class)->deleteGroup($uuid) ?? true, __('waqty.genericError'));

        $this->showDelete = false;
        $this->deletingUuid = null;

        if ($result) {
            $this->loaded = null;
            unset($this->groups);
            $this->dispatch('notify', type: 'success', message: __('common.deleted'));
        }
    }

    public function render()
    {
        return view('livewire.customers.groups');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'G1', 'name' => 'VIP', 'discount_percentage' => 15, 'color' => '#f59e0b', 'description' => 'عملاء أوفياء من الطبقة الأولى.', 'customers_count' => 24],
            ['uuid' => 'G2', 'name' => 'عادي', 'discount_percentage' => 0, 'color' => '#64748b', 'description' => 'عملاء عاديون.', 'customers_count' => 142],
            ['uuid' => 'G3', 'name' => 'جديد', 'discount_percentage' => 5, 'color' => '#3b82f6', 'description' => 'خصم ترحيبي لأول زيارة.', 'customers_count' => 18],
            ['uuid' => 'G4', 'name' => 'طلاب', 'discount_percentage' => 10, 'color' => '#8b5cf6', 'description' => 'سعر خاص للطلاب الموثّقين.', 'customers_count' => 31],
        ];
    }
}
