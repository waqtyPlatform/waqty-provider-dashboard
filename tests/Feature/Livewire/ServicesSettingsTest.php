<?php

declare(strict_types=1);

use App\Livewire\Settings\Services;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('lists services in the settings catalog table', function () {
    Http::fake([
        '*/api/provider/service-prices*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'P1', 'service_uuid' => 'S1', 'branch_uuid' => null, 'employee_uuid' => null, 'pricing_group_uuid' => null, 'price' => 15000, 'active' => true],
        ]]),
        '*/api/provider/services*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'S1', 'name' => 'Classic Haircut', 'sub_category' => ['name' => 'Hair'], 'estimated_duration_minutes' => 30, 'active' => true],
            ['uuid' => 'S2', 'name' => 'Manicure', 'sub_category' => ['name' => 'Nails'], 'estimated_duration_minutes' => 45, 'active' => false],
        ]]),
    ]);

    Livewire::test(Services::class)
        ->assertSee('Classic Haircut')
        ->assertSee('Manicure')
        ->assertSee('150 EGP');
});

it('filters the settings catalog by search', function () {
    Http::fake([
        '*/api/provider/service-prices*' => Http::response(['success' => true, 'data' => []]),
        '*/api/provider/services*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'S1', 'name' => 'Classic Haircut', 'sub_category' => ['name' => 'Hair'], 'estimated_duration_minutes' => 30, 'active' => true],
            ['uuid' => 'S2', 'name' => 'Manicure', 'sub_category' => ['name' => 'Nails'], 'estimated_duration_minutes' => 45, 'active' => false],
        ]]),
    ]);

    Livewire::test(Services::class)
        ->set('search', 'manicure')
        ->assertSee('Manicure')
        ->assertDontSee('Classic Haircut');
});

it('validates the settings service create form', function () {
    Http::fake([
        '*/api/provider/service-prices*' => Http::response(['success' => true, 'data' => []]),
        '*/api/provider/services*' => Http::response(['success' => true, 'data' => []]),
    ]);

    Livewire::test(Services::class)
        ->call('openCreate')
        ->set('form_name', '')
        ->set('form_duration', 2)
        ->call('save')
        ->assertHasErrors(['form_name' => 'required', 'form_duration' => 'min'])
        ->assertSet('showForm', true);
});

it('creates a service from the settings catalog', function () {
    Http::fake([
        '*/api/provider/service-prices*' => Http::response(['success' => true, 'data' => []]),
        '*/api/provider/services*' => function ($request) {
            if ($request->method() === 'POST') {
                return Http::response(['success' => true, 'data' => ['uuid' => 'S9', 'name' => 'Blow Dry']]);
            }

            return Http::response(['success' => true, 'data' => [
                ['uuid' => 'S9', 'name' => 'Blow Dry', 'sub_category' => ['name' => 'Hair'], 'estimated_duration_minutes' => 40, 'active' => true],
            ]]);
        },
    ]);

    Livewire::test(Services::class)
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

it('falls back to sample services when the API is unavailable', function () {
    Http::fake(['*' => Http::response(['message' => 'Server error'], 500)]);

    Livewire::test(Services::class)
        ->assertSee('قصّة شعر كلاسيك')
        ->assertSee('sample data');
});
