<?php

declare(strict_types=1);

namespace App\Livewire\Marketing;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Marketing service groups. Bundling is mock-only in the source, so this
 * manages groups in component state — a faithful, fully-interactive port.
 */
#[Layout('components.layouts.app')]
#[Title('Service Groups — Waqty')]
class ServiceGroups extends Component
{
    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    public bool $showForm = false;

    public ?string $editingId = null;

    public string $form_name = '';

    public string $form_color = '#8b5cf6';

    public bool $form_active = true;

    public bool $showDelete = false;

    public ?string $deletingId = null;

    public function mount(): void
    {
        $this->items = [
            ['id' => 'grp-1', 'name' => 'باقة العرائس', 'color' => '#8b5cf6', 'servicesCount' => 4, 'active' => true],
            ['id' => 'grp-2', 'name' => 'العناية بالرجال', 'color' => '#3b82f6', 'servicesCount' => 6, 'active' => true],
            ['id' => 'grp-3', 'name' => 'يوم السبا', 'color' => '#10b981', 'servicesCount' => 3, 'active' => false],
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'form_name']);
        $this->form_color = '#8b5cf6';
        $this->form_active = true;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $id): void
    {
        $g = collect($this->items)->firstWhere('id', $id);
        if (! $g) {
            return;
        }
        $this->editingId = $id;
        $this->form_name = $g['name'];
        $this->form_color = $g['color'];
        $this->form_active = (bool) $g['active'];
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_name' => ['required', 'string', 'max:60'],
            'form_color' => ['required', 'string'],
        ], ['form_name.required' => __('mkt.serviceGroups.nameRequired')]);

        $data = [
            'name' => trim($this->form_name),
            'color' => $this->form_color,
            'active' => $this->form_active,
        ];

        if ($this->editingId) {
            $this->items = array_map(fn ($g) => $g['id'] === $this->editingId ? [...$g, ...$data] : $g, $this->items);
        } else {
            $maxId = (int) collect($this->items)->map(fn ($g) => (int) str_replace('grp-', '', (string) $g['id']))->max();
            $this->items[] = [...$data, 'id' => 'grp-'.($maxId + 1), 'servicesCount' => 0];
        }

        $this->showForm = false;
        $this->editingId = null;
        $this->dispatch('notify', type: 'success', message: __('common.saved'));
    }

    public function toggleActive(string $id): void
    {
        $this->items = array_map(fn ($g) => $g['id'] === $id ? [...$g, 'active' => ! $g['active']] : $g, $this->items);
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        $this->showDelete = true;
    }

    public function deleteGroup(): void
    {
        $this->items = array_values(array_filter($this->items, fn ($g) => $g['id'] !== $this->deletingId));
        $this->showDelete = false;
        $this->deletingId = null;
        $this->dispatch('notify', type: 'success', message: __('common.deleted'));
    }

    public function render()
    {
        return view('livewire.marketing.service-groups');
    }
}
