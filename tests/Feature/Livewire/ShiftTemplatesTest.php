<?php

declare(strict_types=1);

use App\Livewire\Settings\ShiftTemplates;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('lists shift templates from the API', function () {
    Http::fake(['*/api/provider/shift-templates' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'ST1', 'name' => 'Morning', 'start_time' => '09:00', 'end_time' => '17:00', 'active' => true],
        ['uuid' => 'ST2', 'name' => 'Evening', 'start_time' => '14:00', 'end_time' => '22:00', 'active' => true],
    ]])]);

    Livewire::test(ShiftTemplates::class)
        ->assertSee('Morning')
        ->assertSee('Evening');
});

it('creates a shift template', function () {
    Http::fake(['*/api/provider/shift-templates' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'ST1', 'name' => 'Morning', 'start_time' => '09:00', 'end_time' => '17:00', 'active' => true],
    ]])]);

    Livewire::test(ShiftTemplates::class)
        ->call('openCreate')
        ->set('form_name', 'Night')
        ->set('form_start_time', '22:00')
        ->set('form_end_time', '06:00')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/shift-templates')
        && $req['name'] === 'Night'
        && $req['start_time'] === '22:00');
});

it('falls back to sample shift templates when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(ShiftTemplates::class)
        ->assertSee('sample data')
        ->assertSee('صباحية');
});
