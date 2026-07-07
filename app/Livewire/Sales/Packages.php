<?php

declare(strict_types=1);

namespace App\Livewire\Sales;

use App\Support\Money;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Service packages. The source `salesApi` package CRUD is not wired to a live
 * endpoint (client-only), so this manages packages in component state — a
 * faithful, fully-interactive port of the mock catalogue.
 */
#[Layout('components.layouts.app')]
#[Title('Packages — Waqty')]
class Packages extends Component
{
    public string $search = '';

    /** @var array<int, array<string, mixed>> */
    public array $packages = [];

    // Create/edit slide-over
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $form_name = '';

    public int $form_sessions = 5;

    public int $form_validity = 90;

    public string $form_price = '';

    public bool $form_active = true;

    // Delete
    public bool $showDelete = false;

    public ?int $deletingId = null;

    public function mount(): void
    {
        $this->packages = [
            ['id' => 1, 'name' => 'إشراقة الصيف', 'sessions' => 5, 'validity' => 90, 'price' => 200000, 'active' => true, 'sold' => 34],
            ['id' => 2, 'name' => 'بهجة العروس', 'sessions' => 8, 'validity' => 120, 'price' => 550000, 'active' => true, 'sold' => 12],
            ['id' => 3, 'name' => 'قصّة الرجال', 'sessions' => 10, 'validity' => 180, 'price' => 120000, 'active' => true, 'sold' => 58],
            ['id' => 4, 'name' => 'استراحة السبا', 'sessions' => 6, 'validity' => 90, 'price' => 300000, 'active' => false, 'sold' => 21],
            ['id' => 5, 'name' => 'عناية الأظافر بلس', 'sessions' => 4, 'validity' => 60, 'price' => 90000, 'active' => true, 'sold' => 43],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    #[Computed]
    public function filtered(): array
    {
        $search = trim(mb_strtolower($this->search));
        if ($search === '') {
            return $this->packages;
        }

        return array_values(array_filter($this->packages, fn ($p) => str_contains(mb_strtolower((string) $p['name']), $search)));
    }

    /** @return array{total:int, active:int, sold:int, revenue:int} */
    #[Computed]
    public function kpis(): array
    {
        return [
            'total' => count($this->packages),
            'active' => count(array_filter($this->packages, fn ($p) => $p['active'])),
            'sold' => array_sum(array_map(fn ($p) => (int) $p['sold'], $this->packages)),
            'revenue' => array_sum(array_map(fn ($p) => (int) $p['sold'] * (int) $p['price'], $this->packages)),
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'form_name', 'form_price']);
        $this->form_sessions = 5;
        $this->form_validity = 90;
        $this->form_active = true;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $pkg = collect($this->packages)->firstWhere('id', $id);
        if (! $pkg) {
            return;
        }
        $this->editingId = $id;
        $this->form_name = $pkg['name'];
        $this->form_sessions = (int) $pkg['sessions'];
        $this->form_validity = (int) $pkg['validity'];
        $this->form_price = (string) Money::fromMinor((int) $pkg['price']);
        $this->form_active = (bool) $pkg['active'];
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_name' => ['required', 'string', 'max:80'],
            'form_sessions' => ['required', 'integer', 'min:1', 'max:100'],
            'form_validity' => ['required', 'integer', 'min:1', 'max:730'],
            'form_price' => ['required', 'numeric', 'min:0'],
        ]);

        $data = [
            'name' => trim($this->form_name),
            'sessions' => $this->form_sessions,
            'validity' => $this->form_validity,
            'price' => Money::toMinor((float) $this->form_price),
            'active' => $this->form_active,
        ];

        if ($this->editingId) {
            $this->packages = array_map(fn ($p) => $p['id'] === $this->editingId ? [...$p, ...$data] : $p, $this->packages);
        } else {
            $this->packages[] = [...$data, 'id' => (int) (collect($this->packages)->max('id') ?? 0) + 1, 'sold' => 0];
        }

        $this->showForm = false;
        $this->editingId = null;
        unset($this->filtered, $this->kpis);
        $this->dispatch('notify', type: 'success', message: __('sales.msgPackageCreated'));
    }

    public function toggleActive(int $id): void
    {
        $this->packages = array_map(fn ($p) => $p['id'] === $id ? [...$p, 'active' => ! $p['active']] : $p, $this->packages);
        unset($this->filtered, $this->kpis);
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDelete = true;
    }

    public function deletePackage(): void
    {
        $this->packages = array_values(array_filter($this->packages, fn ($p) => $p['id'] !== $this->deletingId));
        $this->showDelete = false;
        $this->deletingId = null;
        unset($this->filtered, $this->kpis);
        $this->dispatch('notify', type: 'success', message: __('common.deleted'));
    }

    public function render()
    {
        return view('livewire.sales.packages');
    }
}
