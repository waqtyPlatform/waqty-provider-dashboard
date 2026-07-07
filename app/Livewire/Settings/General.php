<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * General business settings. UI-only in the source (localStorage-backed, no
 * API), so toggles persist to the session for the lifetime of the demo.
 */
#[Layout('components.layouts.app')]
#[Title('Settings — Waqty')]
class General extends Component
{
    public bool $onlineBooking = true;

    public bool $walkIn = true;

    public bool $smsReminders = true;

    public bool $autoConfirm = false;

    public bool $requireDeposit = false;

    public int $defaultGap = 15;

    public int $cancellationWindow = 24;

    public function mount(): void
    {
        foreach (session('waqty.settings.general', []) as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    public function save(): void
    {
        $this->validate([
            'defaultGap' => ['required', 'integer', 'min:0', 'max:120'],
            'cancellationWindow' => ['required', 'integer', 'min:0', 'max:168'],
        ]);

        session(['waqty.settings.general' => [
            'onlineBooking' => $this->onlineBooking,
            'walkIn' => $this->walkIn,
            'smsReminders' => $this->smsReminders,
            'autoConfirm' => $this->autoConfirm,
            'requireDeposit' => $this->requireDeposit,
            'defaultGap' => $this->defaultGap,
            'cancellationWindow' => $this->cancellationWindow,
        ]]);

        $this->dispatch('notify', type: 'success', message: __('settings.saved'));
    }

    public function render()
    {
        return view('livewire.settings.general');
    }
}
