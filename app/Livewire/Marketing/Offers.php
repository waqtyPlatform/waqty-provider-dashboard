<?php

declare(strict_types=1);

namespace App\Livewire\Marketing;

use App\Support\Money;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Marketing offers. `marketingApi` is mock-only in the source, so this manages
 * offers in component state — a faithful, fully-interactive port.
 */
#[Layout('components.layouts.app')]
#[Title('Offers — Waqty')]
class Offers extends Component
{
    public string $search = '';

    /** @var array<int, array<string, mixed>> */
    public array $offers = [];

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $form_name = '';

    public string $form_type = 'percentage';

    public string $form_value = '';

    public string $form_start = '';

    public string $form_end = '';

    public string $form_limit = '';

    public bool $form_active = true;

    public bool $showDelete = false;

    public ?int $deletingId = null;

    public function mount(): void
    {
        $today = Carbon::today();
        $this->offers = [
            ['id' => 1, 'name' => 'انطلاقة الصيف', 'type' => 'percentage', 'value' => 20, 'start' => $today->copy()->subDays(5)->toDateString(), 'end' => $today->copy()->addDays(25)->toDateString(), 'limit' => 200, 'used' => 84, 'active' => true],
            ['id' => 2, 'name' => 'ترحيب العملاء الجدد', 'type' => 'fixed', 'value' => 5000, 'start' => $today->copy()->subDays(30)->toDateString(), 'end' => $today->copy()->addDays(60)->toDateString(), 'limit' => 0, 'used' => 143, 'active' => true],
            ['id' => 3, 'name' => 'مكافأة الولاء', 'type' => 'percentage', 'value' => 15, 'start' => $today->copy()->subDays(10)->toDateString(), 'end' => $today->copy()->addDays(20)->toDateString(), 'limit' => 100, 'used' => 37, 'active' => true],
            ['id' => 4, 'name' => 'عرض أيام الأسبوع', 'type' => 'percentage', 'value' => 10, 'start' => $today->copy()->subDays(60)->toDateString(), 'end' => $today->copy()->subDays(5)->toDateString(), 'limit' => 0, 'used' => 210, 'active' => false],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    #[Computed]
    public function filtered(): array
    {
        $search = trim(mb_strtolower($this->search));
        if ($search === '') {
            return $this->offers;
        }

        return array_values(array_filter($this->offers, fn ($o) => str_contains(mb_strtolower((string) $o['name']), $search)));
    }

    /** @return array{total:int, active:int, redemptions:int} */
    #[Computed]
    public function kpis(): array
    {
        return [
            'total' => count($this->offers),
            'active' => count(array_filter($this->offers, fn ($o) => $o['active'])),
            'redemptions' => array_sum(array_map(fn ($o) => (int) $o['used'], $this->offers)),
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'form_name', 'form_value', 'form_limit']);
        $this->form_type = 'percentage';
        $this->form_active = true;
        $this->form_start = Carbon::today()->toDateString();
        $this->form_end = Carbon::today()->addDays(30)->toDateString();
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $o = collect($this->offers)->firstWhere('id', $id);
        if (! $o) {
            return;
        }
        $this->editingId = $id;
        $this->form_name = $o['name'];
        $this->form_type = $o['type'];
        $this->form_value = (string) ($o['type'] === 'fixed' ? Money::fromMinor((int) $o['value']) : $o['value']);
        $this->form_start = $o['start'];
        $this->form_end = $o['end'];
        $this->form_limit = (string) $o['limit'];
        $this->form_active = (bool) $o['active'];
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_name' => ['required', 'string', 'max:80'],
            'form_type' => ['required', 'in:percentage,fixed'],
            'form_value' => ['required', 'numeric', 'min:0'],
            'form_start' => ['required', 'date'],
            'form_end' => ['required', 'date', 'after_or_equal:form_start'],
            'form_limit' => ['nullable', 'integer', 'min:0'],
        ], ['form_name.required' => __('marketing.toastOfferNameRequired')]);

        $value = $this->form_type === 'fixed' ? Money::toMinor((float) $this->form_value) : (int) $this->form_value;
        $data = [
            'name' => trim($this->form_name),
            'type' => $this->form_type,
            'value' => $value,
            'start' => $this->form_start,
            'end' => $this->form_end,
            'limit' => (int) ($this->form_limit ?: 0),
            'active' => $this->form_active,
        ];

        if ($this->editingId) {
            $this->offers = array_map(fn ($o) => $o['id'] === $this->editingId ? [...$o, ...$data] : $o, $this->offers);
        } else {
            $this->offers[] = [...$data, 'id' => (int) (collect($this->offers)->max('id') ?? 0) + 1, 'used' => 0];
        }

        $this->showForm = false;
        $this->editingId = null;
        unset($this->filtered, $this->kpis);
        $this->dispatch('notify', type: 'success', message: __('common.saved'));
    }

    public function toggleActive(int $id): void
    {
        $this->offers = array_map(fn ($o) => $o['id'] === $id ? [...$o, 'active' => ! $o['active']] : $o, $this->offers);
        unset($this->filtered, $this->kpis);
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDelete = true;
    }

    public function deleteOffer(): void
    {
        $this->offers = array_values(array_filter($this->offers, fn ($o) => $o['id'] !== $this->deletingId));
        $this->showDelete = false;
        $this->deletingId = null;
        unset($this->filtered, $this->kpis);
        $this->dispatch('notify', type: 'success', message: __('common.deleted'));
    }

    public function render()
    {
        return view('livewire.marketing.offers');
    }
}
