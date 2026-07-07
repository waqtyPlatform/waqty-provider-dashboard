<?php

declare(strict_types=1);

use App\Livewire\Settings\FingerprintAreas;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('lists fingerprint areas', function () {
    Http::fake(['*/api/provider/settings/fingerprint-areas' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'FA1', 'name' => 'Main Reception', 'device' => 'Front Door ZKTeco', 'active' => true],
        ['uuid' => 'FA2', 'name' => 'Staff Room', 'device' => 'Staff Entrance', 'active' => true],
    ]])]);

    Livewire::test(FingerprintAreas::class)
        ->assertSee('Main Reception')
        ->assertSee('Front Door ZKTeco');
});

it('creates a fingerprint area', function () {
    Http::fake(['*/api/provider/settings/fingerprint-areas' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'FA1', 'name' => 'Main Reception', 'device' => 'Front Door ZKTeco', 'active' => true],
    ]])]);

    Livewire::test(FingerprintAreas::class)
        ->call('openCreate')
        ->set('form_name', 'Back Office')
        ->set('form_device', 'Rear Door ZKTeco')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/settings/fingerprint-areas')
        && $req['name'] === 'Back Office');
});

it('falls back to sample fingerprint areas when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(FingerprintAreas::class)
        ->assertSee('sample data')
        ->assertSee('الاستقبال الرئيسي');
});
