<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Read-only audit log. UI-only in the source (no API), so the recent-activity
 * rows live in component state and are filtered locally by the search box.
 */
#[Layout('components.layouts.app')]
#[Title('Audit Log — Waqty')]
class AuditLog extends Component
{
    public string $search = '';

    /** @var array<int, array<string, string|int>> */
    public array $items = [];

    public function mount(): void
    {
        $this->items = [
            ['id' => 1, 'action' => 'تسجيل دخول', 'user' => 'سارة أحمد', 'time' => '10:32 AM'],
            ['id' => 2, 'action' => 'تحديث خدمة', 'user' => 'علي حسن', 'time' => '09:15 AM'],
            ['id' => 3, 'action' => 'حذف حجز', 'user' => 'سارة أحمد', 'time' => 'Yesterday'],
            ['id' => 4, 'action' => 'إضافة موظف', 'user' => 'منى عادل', 'time' => '2d ago'],
            ['id' => 5, 'action' => 'تغيير الإعدادات', 'user' => 'سارة أحمد', 'time' => '3d ago'],
        ];
    }

    /** @return array<int, array<string, string|int>> */
    #[Computed]
    public function filtered(): array
    {
        $search = trim(mb_strtolower($this->search));
        if ($search === '') {
            return $this->items;
        }

        return array_values(array_filter($this->items, fn ($i) => str_contains(mb_strtolower((string) $i['action']), $search)
            || str_contains(mb_strtolower((string) $i['user']), $search)));
    }

    public function render()
    {
        return view('livewire.settings.audit-log');
    }
}
