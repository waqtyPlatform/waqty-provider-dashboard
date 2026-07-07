<?php

declare(strict_types=1);

namespace App\Livewire\Marketing;

use App\Support\Money;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Ad placements. Purchasing/managing promoted placements is mock-only in the
 * source, so this manages placements in component state — no API involved.
 */
#[Layout('components.layouts.app')]
#[Title('Ad Placements — Waqty')]
class Ads extends Component
{
    public string $search = '';

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    public bool $showForm = false;

    public ?string $editingId = null;

    public string $form_name = '';

    public string $form_placement = 'banner';

    public string $form_price = '';

    public string $form_status = 'active';

    public bool $showDelete = false;

    public ?string $deletingId = null;

    public function mount(): void
    {
        $this->items = [
            ['id' => 'ad-1', 'name' => 'بانر الصفحة الرئيسية', 'placement' => 'banner', 'price' => 5000000, 'status' => 'active'],
            ['id' => 'ad-2', 'name' => 'إعلان مميّز', 'placement' => 'featured', 'price' => 2000000, 'status' => 'active'],
            ['id' => 'ad-3', 'name' => 'تمييز في البحث', 'placement' => 'spotlight', 'price' => 3500000, 'status' => 'paused'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    #[Computed]
    public function filtered(): array
    {
        $search = trim(mb_strtolower($this->search));
        if ($search === '') {
            return $this->items;
        }

        return array_values(array_filter($this->items, fn ($a) => str_contains(mb_strtolower((string) $a['name']), $search)));
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'form_name', 'form_price']);
        $this->form_placement = 'banner';
        $this->form_status = 'active';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $id): void
    {
        $a = collect($this->items)->firstWhere('id', $id);
        if (! $a) {
            return;
        }
        $this->editingId = $id;
        $this->form_name = $a['name'];
        $this->form_placement = $a['placement'];
        $this->form_price = (string) Money::fromMinor((int) $a['price']);
        $this->form_status = $a['status'];
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_name' => ['required', 'string', 'max:80'],
            'form_placement' => ['required', 'in:banner,featured,spotlight'],
            'form_price' => ['required', 'numeric', 'min:0'],
            'form_status' => ['required', 'in:active,paused'],
        ]);

        $data = [
            'name' => trim($this->form_name),
            'placement' => $this->form_placement,
            'price' => Money::toMinor((float) $this->form_price),
            'status' => $this->form_status,
        ];

        if ($this->editingId) {
            $this->items = array_map(fn ($a) => $a['id'] === $this->editingId ? [...$a, ...$data] : $a, $this->items);
        } else {
            $maxNum = (int) (collect($this->items)->map(fn ($a) => (int) preg_replace('/\D/', '', (string) $a['id']))->max() ?? 0);
            $this->items[] = [...$data, 'id' => 'ad-'.($maxNum + 1)];
        }

        $this->showForm = false;
        $this->editingId = null;
        unset($this->filtered);
        $this->dispatch('notify', type: 'success', message: __('common.saved'));
    }

    public function toggleStatus(string $id): void
    {
        $this->items = array_map(fn ($a) => $a['id'] === $id ? [...$a, 'status' => $a['status'] === 'active' ? 'paused' : 'active'] : $a, $this->items);
        unset($this->filtered);
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        $this->showDelete = true;
    }

    public function deleteAd(): void
    {
        $this->items = array_values(array_filter($this->items, fn ($a) => $a['id'] !== $this->deletingId));
        $this->showDelete = false;
        $this->deletingId = null;
        unset($this->filtered);
        $this->dispatch('notify', type: 'success', message: __('common.deleted'));
    }

    public function render()
    {
        return view('livewire.marketing.ads');
    }
}
