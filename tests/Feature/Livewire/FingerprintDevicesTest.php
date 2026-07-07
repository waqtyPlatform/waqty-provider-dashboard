<?php

declare(strict_types=1);

use App\Livewire\Settings\FingerprintDevices;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('lists fingerprint devices from the API', function () {
    Http::fake(['*/api/provider/settings/fingerprint-devices' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'FP1', 'name' => 'Front Door ZKTeco', 'ip_address' => '192.168.1.50', 'port' => 4370, 'active' => true],
        ['uuid' => 'FP2', 'name' => 'Staff Entrance', 'ip_address' => '192.168.1.51', 'port' => 4370, 'active' => true],
    ]])]);

    Livewire::test(FingerprintDevices::class)
        ->assertSee('Front Door ZKTeco')
        ->assertSee('192.168.1.51');
});

it('creates a fingerprint device', function () {
    Http::fake(['*/api/provider/settings/fingerprint-devices' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'FP1', 'name' => 'Front Door ZKTeco', 'ip_address' => '192.168.1.50', 'port' => 4370, 'active' => true],
    ]])]);

    Livewire::test(FingerprintDevices::class)
        ->call('openCreate')
        ->set('form_name', 'Reception Scanner')
        ->set('form_ip', '192.168.1.99')
        ->set('form_port', 4370)
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/settings/fingerprint-devices')
        && $req['name'] === 'Reception Scanner'
        && $req['ip_address'] === '192.168.1.99');
});

it('falls back to sample fingerprint devices when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(FingerprintDevices::class)
        ->assertSee('sample data')
        ->assertSee('جهاز الباب الأمامي ZKTeco');
});
