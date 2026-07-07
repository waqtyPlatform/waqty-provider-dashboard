<?php

declare(strict_types=1);
use App\Livewire\Auth\ProviderLogin;

it('redirects unauthenticated visitors from the dashboard to login', function () {
    $this->get('/')->assertredirectContains('/login');
});

it('renders the provider login screen', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Welcome Back')
        ->assertSeeLivewire(ProviderLogin::class);
});

it('allows an authenticated provider to reach the dashboard', function () {
    session([
        config('waqty.session.provider_token') => 'fake-jwt',
        config('waqty.session.provider_profile') => ['name' => 'Demo', 'role' => 'admin', 'business_type' => 'salon'],
    ]);

    $this->get('/')->assertOk()->assertSee('Demo');
});
