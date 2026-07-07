<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Logged-in devices. UI-only in the source (no API): the session list is seeded
 * in state and "Revoke" is a pure local array edit with a success toast.
 */
#[Layout('components.layouts.app')]
#[Title('Devices — Waqty')]
class Devices extends Component
{
    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    public function mount(): void
    {
        $this->items = [
            ['id' => 1, 'name' => 'كروم على ويندوز', 'type' => 'desktop', 'lastActive' => '2h ago', 'location' => 'القاهرة'],
            ['id' => 2, 'name' => 'سفاري على آيفون', 'type' => 'mobile', 'lastActive' => '1d ago', 'location' => 'الجيزة'],
            ['id' => 3, 'name' => 'آيباد برو', 'type' => 'tablet', 'lastActive' => '5d ago', 'location' => 'الإسكندرية'],
        ];
    }

    public function revoke(int $id): void
    {
        $this->items = array_values(array_filter($this->items, fn ($d) => $d['id'] !== $id));
        $this->dispatch('notify', type: 'success', message: __('settings.devices.revoked'));
    }

    public function render()
    {
        return view('livewire.settings.devices');
    }
}
