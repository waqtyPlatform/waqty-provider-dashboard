<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Integrations directory. UI-only in the source (no API), so the connect state
 * lives in component state — toggling an integration is a pure local edit.
 */
#[Layout('components.layouts.app')]
#[Title('Integrations — Waqty')]
class Integrations extends Component
{
    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    public function mount(): void
    {
        $this->items = [
            ['id' => 1, 'name' => 'WhatsApp Business', 'description' => 'أرسل تأكيدات الحجز والتذكيرات عبر واتساب.', 'connected' => true],
            ['id' => 2, 'name' => 'Google Calendar', 'description' => 'زامن المواعيد في الاتجاهين مع تقويم Google.', 'connected' => true],
            ['id' => 3, 'name' => 'Mailchimp', 'description' => 'نمِّ جمهورك وأطلق حملات تسويق عبر البريد الإلكتروني.', 'connected' => false],
            ['id' => 4, 'name' => 'Zapier', 'description' => 'اربط Waqty بآلاف التطبيقات عبر تدفقات عمل آلية.', 'connected' => false],
        ];
    }

    public function toggle(int $id): void
    {
        $connected = false;

        $this->items = array_map(function ($item) use ($id, &$connected) {
            if ($item['id'] === $id) {
                $item['connected'] = ! $item['connected'];
                $connected = $item['connected'];
            }

            return $item;
        }, $this->items);

        $this->dispatch('notify', type: 'success', message: $connected
            ? __('settings.integrations.toastConnected')
            : __('settings.integrations.toastDisconnected'));
    }

    public function render()
    {
        return view('livewire.settings.integrations');
    }
}
