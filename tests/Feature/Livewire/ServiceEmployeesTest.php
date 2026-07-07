<?php

declare(strict_types=1);

use App\Livewire\Settings\ServiceEmployees;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('builds the matrix from the API and seeds assignments', function () {
    Http::fake([
        '*/api/provider/services*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'S1', 'name' => 'Classic Haircut'],
            ['uuid' => 'S2', 'name' => 'Manicure'],
        ]]),
        '*/api/provider/employees*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'E1', 'name' => 'Sara Ahmed'],
            ['uuid' => 'E2', 'name' => 'Omar Khaled'],
        ]]),
        '*/api/provider/settings/service-employees' => Http::response(['success' => true, 'data' => [
            ['service_uuid' => 'S1', 'employee_uuid' => 'E1', 'active' => true],
        ]]),
    ]);

    $component = Livewire::test(ServiceEmployees::class)
        ->assertSee('Classic Haircut')
        ->assertSee('Manicure');

    $assignments = $component->get('assignments');
    expect($assignments['S1']['E1'])->toBeTrue()
        ->and($assignments['S1']['E2'])->toBeFalse()
        ->and($assignments['S2']['E1'])->toBeFalse();
});

it('toggleRow flips every employee for a service', function () {
    Http::fake([
        '*/api/provider/services*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'S1', 'name' => 'Classic Haircut'],
        ]]),
        '*/api/provider/employees*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'E1', 'name' => 'Sara Ahmed'],
            ['uuid' => 'E2', 'name' => 'Omar Khaled'],
        ]]),
        '*/api/provider/settings/service-employees' => Http::response(['success' => true, 'data' => []]),
    ]);

    $component = Livewire::test(ServiceEmployees::class)
        ->call('toggleRow', 'S1');

    $assignments = $component->get('assignments');
    expect($assignments['S1']['E1'])->toBeTrue()
        ->and($assignments['S1']['E2'])->toBeTrue();
});

it('saves the full matrix via a PUT', function () {
    Http::fake([
        '*/api/provider/services*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'S1', 'name' => 'Classic Haircut'],
        ]]),
        '*/api/provider/employees*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'E1', 'name' => 'Sara Ahmed'],
        ]]),
        '*/api/provider/settings/service-employees' => Http::response(['success' => true, 'data' => []]),
    ]);

    Livewire::test(ServiceEmployees::class)
        ->set('assignments.S1.E1', true)
        ->call('saveAll');

    Http::assertSent(fn ($req) => $req->method() === 'PUT'
        && str_contains($req->url(), '/api/provider/settings/service-employees')
        && is_array($req['mappings']));
});

it('falls back to a sample matrix when the API is unavailable', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(ServiceEmployees::class)
        ->assertSet('fallbackUsed', true)
        ->assertSee('قصّة شعر كلاسيك')
        ->assertSee('sample data');
});
