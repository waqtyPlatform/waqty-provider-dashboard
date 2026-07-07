<?php

declare(strict_types=1);

namespace App\Livewire\Marketing;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Marketing campaigns. `marketingApi` campaign CRUD is mock-only in the source,
 * so this manages campaigns in component state — a faithful, local-CRUD port.
 */
#[Layout('components.layouts.app')]
#[Title('Campaigns — Waqty')]
class Campaigns extends Component
{
    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    public bool $showForm = false;

    public ?string $editingId = null;

    public string $form_name = '';

    public string $form_channel = 'sms';

    public string $form_status = 'draft';

    public string $form_audience = 'all';

    public bool $showDelete = false;

    public ?string $deletingId = null;

    public function mount(): void
    {
        $this->items = [
            ['id' => 'camp-1', 'name' => 'حملة تخفيضات الصيف', 'channel' => 'sms', 'status' => 'active', 'audience' => 'all'],
            ['id' => 'camp-2', 'name' => 'معاينة كبار العملاء', 'channel' => 'email', 'status' => 'draft', 'audience' => 'vip'],
            ['id' => 'camp-3', 'name' => 'استعادة العملاء - الربع الأول', 'channel' => 'whatsapp', 'status' => 'ended', 'audience' => 'inactive'],
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'form_name']);
        $this->form_channel = 'sms';
        $this->form_status = 'draft';
        $this->form_audience = 'all';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $id): void
    {
        $c = collect($this->items)->firstWhere('id', $id);
        if (! $c) {
            return;
        }
        $this->editingId = $id;
        $this->form_name = $c['name'];
        $this->form_channel = $c['channel'];
        $this->form_status = $c['status'];
        $this->form_audience = $c['audience'];
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_name' => ['required', 'string', 'max:80'],
            'form_channel' => ['required', 'in:sms,email,whatsapp,push'],
            'form_status' => ['required', 'in:draft,active,ended'],
            'form_audience' => ['required', 'in:all,vip,inactive'],
        ]);

        $data = [
            'name' => trim($this->form_name),
            'channel' => $this->form_channel,
            'status' => $this->form_status,
            'audience' => $this->form_audience,
        ];

        if ($this->editingId !== null) {
            $this->items = array_map(fn ($c) => $c['id'] === $this->editingId ? [...$c, ...$data] : $c, $this->items);
        } else {
            $maxNum = collect($this->items)->map(fn ($c) => (int) str_replace('camp-', '', (string) $c['id']))->max() ?? 0;
            $this->items[] = [...$data, 'id' => 'camp-'.($maxNum + 1)];
        }

        $this->showForm = false;
        $this->editingId = null;
        $this->dispatch('notify', type: 'success', message: __('common.saved'));
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        $this->showDelete = true;
    }

    public function deleteCampaign(): void
    {
        $this->items = array_values(array_filter($this->items, fn ($c) => $c['id'] !== $this->deletingId));
        $this->showDelete = false;
        $this->deletingId = null;
        $this->dispatch('notify', type: 'success', message: __('common.deleted'));
    }

    public function render()
    {
        return view('livewire.marketing.campaigns');
    }
}
