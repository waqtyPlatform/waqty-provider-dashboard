<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Security preferences (2FA, password policy, lockout, session timeout).
 * Admin-gated (see config/waqty_roles.php). UI-only in the source — persisted
 * to the session for the lifetime of the demo.
 */
#[Layout('components.layouts.app')]
#[Title('Security — Waqty')]
class Security extends Component
{
    public bool $twoFactor = false;

    public bool $passwordChange = false;

    public bool $lockAttempts = true;

    public int $sessionTimeout = 30;

    public function mount(): void
    {
        foreach (session('waqty.settings.security', []) as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    public function save(): void
    {
        $this->validate([
            'sessionTimeout' => ['required', 'integer', 'min:5', 'max:480'],
        ]);

        session(['waqty.settings.security' => [
            'twoFactor' => $this->twoFactor,
            'passwordChange' => $this->passwordChange,
            'lockAttempts' => $this->lockAttempts,
            'sessionTimeout' => $this->sessionTimeout,
        ]]);

        $this->dispatch('notify', type: 'success', message: __('settings.security.saved'));
    }

    public function render()
    {
        return view('livewire.settings.security');
    }
}
