<?php

declare(strict_types=1);

namespace App\Livewire\Marketing;

use App\Support\Money;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Marketing service packages. Bundling is mock-only in the source, so this
 * manages packages in component state — a faithful, fully-interactive port.
 */
#[Layout('components.layouts.app')]
#[Title('Packages — Waqty')]
class Packages extends Component
{
    public string $search = '';

    /** @var array<int, array<string, mixed>> */
    public array $packages = [];

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $form_name = '';

    public string $form_price = '';

    public string $form_original = '';

    public string $form_sessions = '';

    /** @var array<int, string> */
    public array $form_services = [];

    public bool $form_active = true;

    public bool $showDelete = false;

    public ?int $deletingId = null;

    public function mount(): void
    {
        $this->packages = [
            ['id' => 1, 'name' => 'باقة العرائس', 'price' => 320000, 'original' => 450000, 'sessions' => 5, 'services' => ['svc-makeup', 'svc-skin', 'svc-hammam', 'svc-mani'], 'sold' => 24, 'active' => true],
            ['id' => 2, 'name' => 'باقة العناية بالبشرة', 'price' => 180000, 'original' => 240000, 'sessions' => 6, 'services' => ['svc-skin', 'svc-laser'], 'sold' => 41, 'active' => true],
            ['id' => 3, 'name' => 'باقة الاسترخاء', 'price' => 150000, 'original' => 200000, 'sessions' => 4, 'services' => ['svc-hammam', 'svc-pedi'], 'sold' => 17, 'active' => true],
            ['id' => 4, 'name' => 'باقة الرجال', 'price' => 110000, 'original' => 140000, 'sessions' => 8, 'services' => ['svc-hair', 'svc-hammam'], 'sold' => 9, 'active' => false],
        ];
    }

    /** @return array<string, string> Service slug => Arabic name. */
    #[Computed]
    public function serviceOptions(): array
    {
        return [
            'svc-hair' => 'قص وتصفيف الشعر',
            'svc-color' => 'صبغة الشعر',
            'svc-skin' => 'تنظيف البشرة',
            'svc-mani' => 'مانيكير',
            'svc-pedi' => 'باديكير',
            'svc-makeup' => 'مكياج',
            'svc-hammam' => 'حمام مغربي',
            'svc-laser' => 'ليزر',
        ];
    }

    public function serviceName(string $slug): string
    {
        return $this->serviceOptions[$slug] ?? $slug;
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

    /** @return array{total:int, active:int, sold:int} */
    #[Computed]
    public function kpis(): array
    {
        return [
            'total' => count($this->packages),
            'active' => count(array_filter($this->packages, fn ($p) => $p['active'])),
            'sold' => array_sum(array_map(fn ($p) => (int) $p['sold'], $this->packages)),
        ];
    }

    /** All data on this screen is local demo/sample data. */
    public function usingFallback(): bool
    {
        return true;
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'form_name', 'form_price', 'form_original', 'form_sessions', 'form_services']);
        $this->form_active = true;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $p = collect($this->packages)->firstWhere('id', $id);
        if (! $p) {
            return;
        }
        $this->editingId = $id;
        $this->form_name = $p['name'];
        $this->form_price = (string) Money::fromMinor((int) $p['price']);
        $this->form_original = (string) Money::fromMinor((int) $p['original']);
        $this->form_sessions = (string) $p['sessions'];
        $this->form_services = (array) $p['services'];
        $this->form_active = (bool) $p['active'];
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_name' => ['required', 'string', 'max:80'],
            'form_price' => ['required', 'numeric', 'gt:0'],
            'form_original' => ['nullable', 'numeric', 'min:0'],
            'form_sessions' => ['required', 'integer', 'min:1'],
            'form_services' => ['required', 'array', 'min:1'],
        ], [
            'form_name.required' => __('mkt.packages.nameRequired'),
            'form_price.required' => __('mkt.packages.priceRequired'),
            'form_price.gt' => __('mkt.packages.priceRequired'),
            'form_sessions.required' => __('mkt.packages.sessionsRequired'),
            'form_sessions.min' => __('mkt.packages.sessionsRequired'),
            'form_services.required' => __('mkt.msgSelectOneService'),
            'form_services.min' => __('mkt.msgSelectOneService'),
        ]);

        $price = Money::toMinor((float) $this->form_price);
        $original = $this->form_original !== '' ? Money::toMinor((float) $this->form_original) : $price;

        $data = [
            'name' => trim($this->form_name),
            'price' => $price,
            'original' => max($original, $price),
            'sessions' => (int) $this->form_sessions,
            'services' => array_values($this->form_services),
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
        $this->dispatch('notify', type: 'success', message: __('common.saved'));
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
        return view('livewire.marketing.packages');
    }
}
