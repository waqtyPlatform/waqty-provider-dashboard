<?php

declare(strict_types=1);

namespace App\Livewire\Marketing;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Marketing announcements. `marketingApi` announcement CRUD is mock-only in the
 * source, so this manages announcements in component state.
 */
#[Layout('components.layouts.app')]
#[Title('Announcements — Waqty')]
class Announcements extends Component
{
    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    public bool $showForm = false;

    public ?string $editingId = null;

    public string $form_title = '';

    public string $form_body = '';

    public string $form_priority = 'normal';

    public bool $form_active = true;

    public bool $showDelete = false;

    public ?string $deletingId = null;

    public function mount(): void
    {
        $this->items = [
            ['id' => 'row-1', 'title' => 'مواعيد إجازة العيد', 'body' => 'ستعمل فروعنا بساعات عمل مخفّضة خلال إجازة العيد. يرجى مراجعة جدول فرعك المحلي قبل الزيارة.', 'priority' => 'high', 'active' => true],
            ['id' => 'row-2', 'title' => 'برنامج الولاء الجديد', 'body' => 'اكسب نقاطًا مع كل حجز واستبدلها بخصومات على خدماتك المفضّلة.', 'priority' => 'normal', 'active' => true],
            ['id' => 'row-3', 'title' => 'صيانة النظام', 'body' => 'سيكون نظام الحجز عبر الإنترنت غير متاح لفترة وجيزة أثناء الليل بينما نطرح تحسينات جديدة.', 'priority' => 'low', 'active' => false],
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'form_title', 'form_body']);
        $this->form_priority = 'normal';
        $this->form_active = true;
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
        $this->form_title = $a['title'];
        $this->form_body = $a['body'];
        $this->form_priority = $a['priority'];
        $this->form_active = (bool) $a['active'];
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_title' => ['required', 'string', 'max:100'],
            'form_body' => ['required', 'string', 'max:500'],
            'form_priority' => ['required', 'in:low,normal,high'],
        ], [
            'form_title.required' => __('mkt.msgAnnouncementTitleRequired'),
            'form_body.required' => __('mkt.msgAnnouncementBodyRequired'),
        ]);

        $data = [
            'title' => trim($this->form_title),
            'body' => trim($this->form_body),
            'priority' => $this->form_priority,
            'active' => $this->form_active,
        ];

        if ($this->editingId) {
            $this->items = array_map(fn ($a) => $a['id'] === $this->editingId ? [...$a, ...$data] : $a, $this->items);
        } else {
            $nextNum = (int) collect($this->items)->map(fn ($a) => (int) str_replace('row-', '', (string) $a['id']))->max();
            $this->items[] = [...$data, 'id' => 'row-'.($nextNum + 1)];
        }

        $this->showForm = false;
        $this->editingId = null;
        $this->dispatch('notify', type: 'success', message: __('common.saved'));
    }

    public function toggleActive(string $id): void
    {
        $this->items = array_map(fn ($a) => $a['id'] === $id ? [...$a, 'active' => ! $a['active']] : $a, $this->items);
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        $this->showDelete = true;
    }

    public function deleteAnnouncement(): void
    {
        $this->items = array_values(array_filter($this->items, fn ($a) => $a['id'] !== $this->deletingId));
        $this->showDelete = false;
        $this->deletingId = null;
        $this->dispatch('notify', type: 'success', message: __('common.deleted'));
    }

    public function render()
    {
        return view('livewire.marketing.announcements');
    }
}
