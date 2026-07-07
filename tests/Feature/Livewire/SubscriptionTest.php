<?php

declare(strict_types=1);

use App\Livewire\Settings\Subscription;
use Livewire\Livewire;

it('renders the subscription page with seeded plan and usage literals', function () {
    Livewire::test(Subscription::class)
        ->assertOk()
        ->assertSee('احترافي')
        ->assertSee('$49')
        ->assertSee('$99')
        ->assertSee('320');
});

it('switches the active plan locally when upgrading', function () {
    Livewire::test(Subscription::class)
        ->assertSet('currentKey', 'pro')
        ->call('upgrade', 'enterprise')
        ->assertSet('currentKey', 'enterprise');
});

it('emits a success toast when a plan is upgraded', function () {
    Livewire::test(Subscription::class)
        ->call('upgrade', 'enterprise')
        ->assertDispatched('notify', type: 'success');
});
