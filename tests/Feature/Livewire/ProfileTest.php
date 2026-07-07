<?php

declare(strict_types=1);

use App\Livewire\Settings\Profile;
use Livewire\Livewire;

it('renders the provider profile from the session', function () {
    session([config('waqty.session.provider_profile') => [
        'name' => 'Glow Beauty Clinic',
        'email' => 'owner@glow.example',
        'role' => 'owner',
        'business_type' => 'clinic',
        'category' => ['name' => 'Aesthetics Clinic'],
    ]]);

    Livewire::test(Profile::class)
        ->assertSee('Glow Beauty Clinic')
        ->assertSee('owner@glow.example')
        ->assertSee('Aesthetics Clinic');
});

it('prefers the category name for the business type', function () {
    session([config('waqty.session.provider_profile') => [
        'name' => 'Downtown Barbers',
        'business_type' => 'barber',
        'category' => ['name' => 'Barbershop'],
    ]]);

    Livewire::test(Profile::class)
        ->assertSet('businessType', 'Barbershop');
});

it('falls back to placeholders when the profile is absent', function () {
    Livewire::test(Profile::class)
        ->assertSet('name', '—')
        ->assertSet('email', '—')
        ->assertSet('role', 'مدير')
        ->assertSet('businessType', '—');
});
