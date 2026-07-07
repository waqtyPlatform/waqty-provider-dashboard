<?php

declare(strict_types=1);

namespace App\Livewire\Marketing;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Message templates. `marketingApi` template CRUD is mock-only in the source,
 * so this manages reusable templates in component state.
 */
#[Layout('components.layouts.app')]
#[Title('Message Templates — Waqty')]
class Messages extends Component
{
    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    public bool $showForm = false;

    public ?string $editingId = null;

    public string $form_name = '';

    public string $form_channel = 'sms';

    public string $form_body = '';

    public bool $showDelete = false;

    public ?string $deletingId = null;

    public function mount(): void
    {
        $this->items = [
            ['id' => 'msg-1', 'name' => 'تأكيد الحجز', 'channel' => 'sms', 'body' => 'مرحبًا {name}، تم تأكيد حجزك لخدمة {service} بتاريخ {date}. نراك قريبًا!'],
            ['id' => 'msg-2', 'name' => 'طلب تقييم', 'channel' => 'whatsapp', 'body' => 'شكرًا لزيارتك يا {name}! يسعدنا معرفة رأيك: {link}'],
            ['id' => 'msg-3', 'name' => 'عرض عيد الميلاد', 'channel' => 'email', 'body' => 'عيد ميلاد سعيد يا {name}! استمتع بهدية خاصة في زيارتك القادمة لنا.'],
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'form_name', 'form_body']);
        $this->form_channel = 'sms';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $id): void
    {
        $m = collect($this->items)->firstWhere('id', $id);
        if (! $m) {
            return;
        }
        $this->editingId = $id;
        $this->form_name = $m['name'];
        $this->form_channel = $m['channel'];
        $this->form_body = $m['body'];
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_name' => ['required', 'string', 'max:60'],
            'form_channel' => ['required', 'in:sms,whatsapp,email,push'],
            'form_body' => ['required', 'string', 'max:300'],
        ]);

        $data = [
            'name' => trim($this->form_name),
            'channel' => $this->form_channel,
            'body' => trim($this->form_body),
        ];

        if ($this->editingId) {
            $this->items = array_map(fn ($m) => $m['id'] === $this->editingId ? [...$m, ...$data] : $m, $this->items);
        } else {
            $maxId = collect($this->items)->map(fn ($m) => (int) str_replace('msg-', '', (string) $m['id']))->max() ?? 0;
            $this->items[] = [...$data, 'id' => 'msg-'.($maxId + 1)];
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

    public function deleteMessage(): void
    {
        $this->items = array_values(array_filter($this->items, fn ($m) => $m['id'] !== $this->deletingId));
        $this->showDelete = false;
        $this->deletingId = null;
        $this->dispatch('notify', type: 'success', message: __('common.deleted'));
    }

    public function render()
    {
        return view('livewire.marketing.messages');
    }
}
