<?php

declare(strict_types=1);

use App\Livewire\Employees\AttendMethods;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeAttendanceMethods(): void
{
    Http::fake([
        '*/api/provider/attendance-methods/*' => Http::response(['success' => true], 200),
        '*/api/provider/attendance-methods' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'AM1', 'type' => 'fingerprint', 'name' => 'Fingerprint', 'enabled' => true, 'device_ip' => '192.168.1.50', 'device_port' => 4370],
            ['uuid' => 'AM2', 'type' => 'gps', 'name' => 'GPS Location', 'enabled' => true, 'gps_radius' => 100],
            ['uuid' => 'AM3', 'type' => 'pin', 'name' => 'PIN Code', 'enabled' => false, 'pin_length' => 4],
            ['uuid' => 'AM4', 'type' => 'manual', 'name' => 'Manual Entry', 'enabled' => true, 'require_approval' => true],
        ]]),
    ]);
}

it('renders attendance methods from the API', function () {
    fakeAttendanceMethods();

    Livewire::test(AttendMethods::class)
        ->assertOk()
        ->assertSee('Fingerprint')
        ->assertSee('GPS Location')
        ->assertSee('192.168.1.50');
});

it('falls back to Arabic sample methods when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(AttendMethods::class)
        ->assertSee('sample data')
        ->assertSee('البصمة')
        ->assertSee('التسجيل اليدوي');
});

it('toggles an attendance method and calls the API', function () {
    fakeAttendanceMethods();

    Livewire::test(AttendMethods::class)
        ->call('toggleAttendanceMethod', 'AM3')
        ->assertSet('overrides.AM3', true);

    Http::assertSent(fn ($req) => $req->method() === 'PATCH'
        && str_contains($req->url(), '/api/provider/attendance-methods/AM3'));
});

it('validates the fingerprint configuration', function () {
    fakeAttendanceMethods();

    Livewire::test(AttendMethods::class)
        ->call('configure', 'AM1')
        ->set('form_device_ip', '')
        ->set('form_device_port', '')
        ->call('saveConfig')
        ->assertHasErrors(['form_device_ip', 'form_device_port'])
        ->assertSet('showConfig', true);
});

it('saves a configuration and notifies', function () {
    fakeAttendanceMethods();

    Livewire::test(AttendMethods::class)
        ->call('configure', 'AM2')
        ->set('form_gps_radius', '250')
        ->call('saveConfig')
        ->assertHasNoErrors()
        ->assertSet('showConfig', false)
        ->assertDispatched('notify');

    Http::assertSent(fn ($req) => $req->method() === 'PUT'
        && str_contains($req->url(), '/api/provider/attendance-methods/AM2')
        && (int) $req['gps_radius'] === 250);
});
