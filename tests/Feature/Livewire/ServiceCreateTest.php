<?php

declare(strict_types=1);

use App\Livewire\Settings\ServiceCreate;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('renders the wizard on the first step', function () {
    Livewire::test(ServiceCreate::class)
        ->assertSet('step', 1)
        ->assertSee('Service Name')
        ->assertSee('Add New Service');
});

it('requires a service name before advancing past step one', function () {
    Livewire::test(ServiceCreate::class)
        ->set('name', '')
        ->call('next')
        ->assertHasErrors(['name' => 'required'])
        ->assertSet('step', 1);
});

it('advances through every step once each is valid', function () {
    Livewire::test(ServiceCreate::class)
        ->set('name', 'Blow Dry')
        ->call('next')->assertSet('step', 2)
        ->set('price', '120')
        ->call('next')->assertSet('step', 3)
        ->set('duration', 40)
        ->call('next')->assertSet('step', 4)
        ->set('resource', 'chair')
        ->set('capacity', 2)
        ->call('next')->assertSet('step', 5)
        ->set('commission', '15')
        ->call('next')->assertSet('step', 6)
        ->assertHasNoErrors();
});

it('rejects an out-of-range duration', function () {
    Livewire::test(ServiceCreate::class)
        ->set('name', 'Blow Dry')
        ->call('next')
        ->call('next') // step 2 -> 3
        ->set('duration', 2)
        ->call('next')
        ->assertHasErrors(['duration' => 'min'])
        ->assertSet('step', 3);
});

it('creates the service and redirects to the catalog', function () {
    Http::fake([
        '*/api/provider/services*' => Http::response(['success' => true, 'data' => ['uuid' => 'S9', 'name' => 'Blow Dry']]),
    ]);

    Livewire::test(ServiceCreate::class)
        ->set('step', 6)
        ->set('name', 'Blow Dry')
        ->set('price', '120')
        ->set('duration', 40)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('settings.services'));

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/api/provider/services'));
});

it('still confirms success and redirects when the API is unavailable', function () {
    Http::fake(['*' => Http::response(['message' => 'Server error'], 500)]);

    Livewire::test(ServiceCreate::class)
        ->set('step', 6)
        ->set('name', 'Blow Dry')
        ->set('duration', 30)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('settings.services'));
});
