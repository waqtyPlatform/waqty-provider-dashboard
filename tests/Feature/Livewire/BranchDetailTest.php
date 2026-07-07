<?php

declare(strict_types=1);

use App\Livewire\Settings\BranchDetail;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('loads a branch into the general tab', function () {
    Http::fake([
        '*/api/provider/branches/BR1' => Http::response(['success' => true, 'data' => [
            'uuid' => 'BR1', 'name' => 'فرع وسط البلد', 'phone' => '011 2345 6789', 'city' => 'Cairo',
            'latitude' => 30.0444, 'longitude' => 31.2357, 'geofence_radius' => 200, 'require_gps' => true,
        ]]),
    ]);

    Livewire::test(BranchDetail::class, ['uuid' => 'BR1'])
        ->assertSet('form_name', 'فرع وسط البلد')
        ->assertSet('form_city', 'Cairo')
        ->assertSet('form_radius', 200)
        ->assertSet('form_require_gps', true)
        ->assertSee('فرع وسط البلد');
});

it('saves the general tab via a PUT', function () {
    Http::fake([
        '*/api/provider/branches/BR1' => Http::response(['success' => true, 'data' => [
            'uuid' => 'BR1', 'name' => 'فرع وسط البلد', 'phone' => '011', 'city' => 'Cairo',
        ]]),
    ]);

    Livewire::test(BranchDetail::class, ['uuid' => 'BR1'])
        ->set('form_name', 'مقر وسط البلد')
        ->call('saveGeneral')
        ->assertHasNoErrors();

    Http::assertSent(fn ($req) => $req->method() === 'PUT'
        && str_contains($req->url(), '/api/provider/branches/BR1')
        && $req['name'] === 'مقر وسط البلد');
});

it('saves geofence settings via a PUT', function () {
    Http::fake([
        '*/api/provider/branches/BR1' => Http::response(['success' => true, 'data' => [
            'uuid' => 'BR1', 'name' => 'فرع وسط البلد',
        ]]),
    ]);

    Livewire::test(BranchDetail::class, ['uuid' => 'BR1'])
        ->set('tab', 'geofence')
        ->set('form_latitude', '30.05')
        ->set('form_radius', 250)
        ->call('saveGeofence')
        ->assertHasNoErrors();

    Http::assertSent(fn ($req) => $req->method() === 'PUT'
        && $req['geofence_radius'] === 250
        && $req['latitude'] === 30.05);
});

it('adds and removes a local room', function () {
    Http::fake([
        '*/api/provider/branches/BR1' => Http::response(['success' => true, 'data' => ['uuid' => 'BR1', 'name' => 'فرع وسط البلد']]),
    ]);

    $component = Livewire::test(BranchDetail::class, ['uuid' => 'BR1'])
        ->set('tab', 'rooms')
        ->set('room_name', 'VIP Suite')
        ->set('room_capacity', 4)
        ->call('addRoom')
        ->assertHasNoErrors();

    $rooms = $component->get('rooms');
    $added = collect($rooms)->firstWhere('name', 'VIP Suite');
    expect($added)->not->toBeNull()->and($added['capacity'])->toBe(4);

    $component->call('removeRoom', $added['id']);
    expect(collect($component->get('rooms'))->firstWhere('name', 'VIP Suite'))->toBeNull();
});

it('falls back to a sample branch when the API is unavailable', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(BranchDetail::class, ['uuid' => 'BR1'])
        ->assertSet('fallbackUsed', true)
        ->assertSee('فرع وسط البلد')
        ->assertSee('sample data');
});
