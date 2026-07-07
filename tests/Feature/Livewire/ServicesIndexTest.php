<?php

declare(strict_types=1);

use App\Livewire\Services\Index;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeServices(): void
{
    Http::fake([
        '*/api/provider/service-prices*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'P1', 'service_uuid' => 'S1', 'branch_uuid' => null, 'employee_uuid' => null, 'pricing_group_uuid' => null, 'price' => 15000, 'active' => true],
        ]]),
        '*/api/provider/services*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'S1', 'name' => 'Classic Haircut', 'name_ar' => 'قصة شعر', 'sub_category' => ['name' => 'Hair'], 'estimated_duration_minutes' => 30, 'active' => true],
            ['uuid' => 'S2', 'name' => 'Manicure', 'sub_category' => ['name' => 'Nails'], 'estimated_duration_minutes' => 45, 'active' => false],
        ]]),
    ]);
}

it('lists services with a resolved base price', function () {
    fakeServices();

    Livewire::test(Index::class)
        ->assertSee('Classic Haircut')
        ->assertSee('Manicure')
        ->assertSee('150 EGP'); // 15000 minor units merged from service-prices
});

it('filters services by search', function () {
    fakeServices();

    Livewire::test(Index::class)
        ->set('search', 'haircut')
        ->assertSee('Classic Haircut')
        ->assertDontSee('Manicure');
});

it('filters services by category', function () {
    fakeServices();

    Livewire::test(Index::class)
        ->set('categoryFilter', 'Nails')
        ->assertSee('Manicure')
        ->assertDontSee('Classic Haircut');
});

it('toggles service active state via the API', function () {
    fakeServices();

    Livewire::test(Index::class)
        ->call('toggleActive', 'S1')
        ->assertSet('overrides.S1', false);

    Http::assertSent(fn ($req) => $req->method() === 'PATCH'
        && str_contains($req->url(), '/api/provider/services/S1/active'));
});

it('validates the create form', function () {
    fakeServices();

    Livewire::test(Index::class)
        ->call('openCreate')
        ->set('form_name', '')
        ->set('form_duration', 2) // below the 5-minute minimum
        ->call('save')
        ->assertHasErrors(['form_name' => 'required', 'form_duration' => 'min'])
        ->assertSet('showForm', true);
});

it('creates a service and closes the slide-over', function () {
    fakeServices();

    Livewire::test(Index::class)
        ->call('openCreate')
        ->set('form_name', 'Blow Dry')
        ->set('form_duration', 40)
        ->set('form_price', '120')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/api/provider/services'));
});

it('falls back to sample data when the API is unavailable', function () {
    Http::fake(['*/api/provider/services*' => Http::response(['message' => 'Server error'], 500)]);

    Livewire::test(Index::class)
        ->assertSee('Classic Haircut') // fallback sample
        ->assertSee('sample data');
});
