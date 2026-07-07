<?php

declare(strict_types=1);

namespace App\Livewire\Marketing;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Push notifications. `marketingApi` push CRUD is mock-only in the source, so
 * this composes and manages notifications entirely in component state.
 */
#[Layout('components.layouts.app')]
#[Title('Push Notifications — Waqty')]
class MarketingNotifications extends Component
{
    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    public bool $showForm = false;

    public ?string $editingId = null;

    public string $form_title = '';

    public string $form_body = '';

    public string $form_audience = 'all';

    public bool $showDelete = false;

    public ?string $deletingId = null;

    public function mount(): void
    {
        $this->items = [
            ['id' => 'ntf-1', 'title' => 'تخفيضات سريعة اليوم!', 'body' => 'احصل على خصم 25% على جميع الخدمات قبل منتصف الليل. احجز الآن ووفّر الكثير!', 'audience' => 'all', 'sentAt' => 'منذ ساعتين'],
            ['id' => 'ntf-2', 'title' => 'عرض حصري لكبار العملاء', 'body' => 'مكافأة خاصة بانتظار كبار عملائنا في نهاية هذا الأسبوع فقط.', 'audience' => 'vip', 'sentAt' => 'منذ يوم'],
            ['id' => 'ntf-3', 'title' => 'اشتقنا إليك', 'body' => 'لقد مرّ وقت طويل! عد إلينا للحصول على استشارة مجانية.', 'audience' => 'inactive', 'sentAt' => 'Draft'],
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'form_title', 'form_body']);
        $this->form_audience = 'all';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $id): void
    {
        $n = collect($this->items)->firstWhere('id', $id);
        if (! $n) {
            return;
        }
        $this->editingId = $id;
        $this->form_title = $n['title'];
        $this->form_body = $n['body'];
        $this->form_audience = $n['audience'];
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_title' => ['required', 'string', 'max:80'],
            'form_body' => ['required', 'string', 'max:200'],
            'form_audience' => ['required', 'in:all,vip,inactive'],
        ]);

        $data = [
            'title' => trim($this->form_title),
            'body' => trim($this->form_body),
            'audience' => $this->form_audience,
        ];

        if ($this->editingId) {
            $this->items = array_map(fn ($n) => $n['id'] === $this->editingId ? [...$n, ...$data] : $n, $this->items);
        } else {
            $this->items[] = [...$data, 'id' => 'ntf-'.$this->nextId(), 'sentAt' => 'Draft'];
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

    public function deleteNotification(): void
    {
        $this->items = array_values(array_filter($this->items, fn ($n) => $n['id'] !== $this->deletingId));
        $this->showDelete = false;
        $this->deletingId = null;
        $this->dispatch('notify', type: 'success', message: __('common.deleted'));
    }

    private function nextId(): int
    {
        $max = 0;
        foreach ($this->items as $n) {
            $max = max($max, (int) str_replace('ntf-', '', (string) $n['id']));
        }

        return $max + 1;
    }

    public function render()
    {
        return view('livewire.marketing.notifications');
    }
}
