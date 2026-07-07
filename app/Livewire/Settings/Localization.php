<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Regional preferences (timezone / currency / date format). UI-only in the
 * source — persisted to the session for the lifetime of the demo rather than
 * routed through the optional GET/PATCH endpoint.
 */
#[Layout('components.layouts.app')]
#[Title('Localization — Waqty')]
class Localization extends Component
{
    public string $timezone = 'Africa/Cairo';

    public string $currency = 'EGP';

    public string $dateFormat = 'DD/MM/YYYY';

    public function mount(): void
    {
        foreach (session('waqty.settings.localization', []) as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    public function save(): void
    {
        $this->validate([
            'timezone' => ['required', 'string', 'in:Africa/Cairo,Asia/Riyadh,Asia/Dubai,UTC'],
            'currency' => ['required', 'in:EGP,SAR,AED,USD'],
            'dateFormat' => ['required', 'in:DD/MM/YYYY,MM/DD/YYYY,YYYY-MM-DD'],
        ]);

        session(['waqty.settings.localization' => [
            'timezone' => $this->timezone,
            'currency' => $this->currency,
            'dateFormat' => $this->dateFormat,
        ]]);

        $this->dispatch('notify', type: 'success', message: __('settings.localization.saved'));
    }

    public function render()
    {
        return view('livewire.settings.localization');
    }
}
