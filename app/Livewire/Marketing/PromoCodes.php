<?php

declare(strict_types=1);

namespace App\Livewire\Marketing;

use App\Support\Money;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Promo codes. `marketingApi` promo CRUD is mock-only in the source, so this
 * manages codes in component state.
 */
#[Layout('components.layouts.app')]
#[Title('Promo Codes — Waqty')]
class PromoCodes extends Component
{
    public string $search = '';

    /** @var array<int, array<string, mixed>> */
    public array $codes = [];

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $form_code = '';

    public string $form_type = 'percentage';

    public string $form_value = '';

    public string $form_limit = '';

    public bool $form_active = true;

    public bool $showDelete = false;

    public ?int $deletingId = null;

    public function mount(): void
    {
        $this->codes = [
            ['id' => 1, 'code' => 'SUMMER20', 'type' => 'percentage', 'value' => 20, 'limit' => 200, 'used' => 84, 'active' => true],
            ['id' => 2, 'code' => 'WELCOME50', 'type' => 'fixed', 'value' => 5000, 'limit' => 0, 'used' => 143, 'active' => true],
            ['id' => 3, 'code' => 'VIP15', 'type' => 'percentage', 'value' => 15, 'limit' => 100, 'used' => 37, 'active' => true],
            ['id' => 4, 'code' => 'FLASH10', 'type' => 'percentage', 'value' => 10, 'limit' => 50, 'used' => 50, 'active' => false],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    #[Computed]
    public function filtered(): array
    {
        $search = trim(mb_strtoupper($this->search));
        if ($search === '') {
            return $this->codes;
        }

        return array_values(array_filter($this->codes, fn ($c) => str_contains(mb_strtoupper((string) $c['code']), $search)));
    }

    /** @return array{total:int, active:int, redemptions:int} */
    #[Computed]
    public function kpis(): array
    {
        return [
            'total' => count($this->codes),
            'active' => count(array_filter($this->codes, fn ($c) => $c['active'])),
            'redemptions' => array_sum(array_map(fn ($c) => (int) $c['used'], $this->codes)),
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'form_code', 'form_value', 'form_limit']);
        $this->form_type = 'percentage';
        $this->form_active = true;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $c = collect($this->codes)->firstWhere('id', $id);
        if (! $c) {
            return;
        }
        $this->editingId = $id;
        $this->form_code = $c['code'];
        $this->form_type = $c['type'];
        $this->form_value = (string) ($c['type'] === 'fixed' ? Money::fromMinor((int) $c['value']) : $c['value']);
        $this->form_limit = (string) $c['limit'];
        $this->form_active = (bool) $c['active'];
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        // Normalise to uppercase before validating so lowercase input is accepted.
        $this->form_code = mb_strtoupper(trim($this->form_code));

        $this->validate([
            'form_code' => ['required', 'string', 'regex:/^[A-Z0-9]{3,20}$/'],
            'form_type' => ['required', 'in:percentage,fixed'],
            'form_value' => ['required', 'numeric', 'min:0', $this->form_type === 'percentage' ? 'max:100' : 'max:1000000'],
            'form_limit' => ['nullable', 'integer', 'min:0'],
        ], ['form_code.regex' => __('marketing.toastPromoRequired'), 'form_code.required' => __('marketing.toastPromoRequired')]);

        $value = $this->form_type === 'fixed' ? Money::toMinor((float) $this->form_value) : (int) $this->form_value;
        $data = [
            'code' => mb_strtoupper(trim($this->form_code)),
            'type' => $this->form_type,
            'value' => $value,
            'limit' => (int) ($this->form_limit ?: 0),
            'active' => $this->form_active,
        ];

        if ($this->editingId) {
            $this->codes = array_map(fn ($c) => $c['id'] === $this->editingId ? [...$c, ...$data] : $c, $this->codes);
        } else {
            $this->codes[] = [...$data, 'id' => (int) (collect($this->codes)->max('id') ?? 0) + 1, 'used' => 0];
        }

        $this->showForm = false;
        $this->editingId = null;
        unset($this->filtered, $this->kpis);
        $this->dispatch('notify', type: 'success', message: __('common.saved'));
    }

    public function toggleActive(int $id): void
    {
        $this->codes = array_map(fn ($c) => $c['id'] === $id ? [...$c, 'active' => ! $c['active']] : $c, $this->codes);
        unset($this->filtered, $this->kpis);
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDelete = true;
    }

    public function deleteCode(): void
    {
        $this->codes = array_values(array_filter($this->codes, fn ($c) => $c['id'] !== $this->deletingId));
        $this->showDelete = false;
        $this->deletingId = null;
        unset($this->filtered, $this->kpis);
        $this->dispatch('notify', type: 'success', message: __('common.deleted'));
    }

    public function render()
    {
        return view('livewire.marketing.promo-codes');
    }
}
