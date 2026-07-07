<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Business profile. Read-only summary of the signed-in provider — reads the
 * cached provider profile from the session (populated by AuthService on login)
 * and renders it as plain info rows. No form, no edit, no API.
 */
#[Layout('components.layouts.app')]
#[Title('Business Profile — Waqty')]
class Profile extends Component
{
    public string $name = '—';

    public string $email = '—';

    public string $role = 'مدير';

    public string $businessType = '—';

    public function mount(): void
    {
        $profile = session(config('waqty.session.provider_profile'), []);

        $this->name = (string) ($profile['name'] ?? '—') ?: '—';
        $this->email = (string) ($profile['email'] ?? '—') ?: '—';
        $this->role = (string) ($profile['role'] ?? 'مدير') ?: 'مدير';

        $category = data_get($profile, 'category.name');
        $this->businessType = (string) ($category ?? $profile['business_type'] ?? '—') ?: '—';
    }

    public function render()
    {
        return view('livewire.settings.profile');
    }
}
