<?php

declare(strict_types=1);

use App\Livewire\Settings\Branches;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('lists branches from the API', function () {
    Http::fake(['*/api/provider/branches' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'BR1', 'name' => 'Downtown Branch', 'phone' => '011 2345 6789', 'city' => 'Cairo', 'active' => true, 'is_main' => true],
        ['uuid' => 'BR2', 'name' => 'Mall of Arabia', 'phone' => '012 3456 7890', 'city' => 'Giza', 'active' => true, 'is_main' => false],
    ]])]);

    Livewire::test(Branches::class)
        ->assertSee('Downtown Branch')
        ->assertSee('Mall of Arabia');
});

it('creates a branch', function () {
    Http::fake(['*/api/provider/branches' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'BR1', 'name' => 'Downtown Branch', 'phone' => '011 2345 6789', 'city' => 'Cairo', 'active' => true, 'is_main' => true],
    ]])]);

    Livewire::test(Branches::class)
        ->call('openCreate')
        ->set('form_name', 'Alexandria Branch')
        ->set('form_phone', '033 111 2222')
        ->set('form_city', 'Alexandria')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/api/provider/branches')
        && $req['name'] === 'Alexandria Branch'
        && $req['city'] === 'Alexandria');
});

it('falls back to sample branches when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Branches::class)
        ->assertSee('sample data')
        ->assertSee('فرع وسط البلد');
});
