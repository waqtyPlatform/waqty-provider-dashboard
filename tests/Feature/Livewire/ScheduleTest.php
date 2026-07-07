<?php

declare(strict_types=1);

use App\Livewire\Employees\Schedule;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeShifts(): void
{
    Http::fake([
        '*/api/provider/shifts*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'SH1', 'employee' => 'Sara Ahmed', 'day' => 'sun', 'start' => '10:00', 'end' => '18:00'],
            ['uuid' => 'SH2', 'employee' => 'Khaled Hassan', 'day' => 'mon', 'start' => '09:00', 'end' => '17:00'],
        ]]),
    ]);
}

it('renders the weekly grid from the API', function () {
    fakeShifts();

    Livewire::test(Schedule::class)
        ->assertSee('Sara Ahmed')
        ->assertSee('Khaled Hassan')
        ->assertSee('10:00–18:00');
});

it('falls back to Arabic sample shifts when the API is unavailable', function () {
    Http::fake(['*/api/provider/shifts*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Schedule::class)
        ->assertSee('sample data')
        ->assertSee('سارة أحمد')
        ->assertSee('طارق سامي');
});

it('validates the add shift form', function () {
    fakeShifts();

    Livewire::test(Schedule::class)
        ->call('openCreate')
        ->set('form_employee', '')
        ->set('form_day', '')
        ->set('form_start', '')
        ->set('form_end', '')
        ->call('save')
        ->assertHasErrors([
            'form_employee' => 'required',
            'form_day' => 'required',
            'form_start' => 'required',
            'form_end' => 'required',
        ])
        ->assertSet('showForm', true);
});

it('creates a shift, notifies, and sends it to the API', function () {
    fakeShifts();

    Livewire::test(Schedule::class)
        ->call('openCreate', 'Mona Adel', 'wed')
        ->assertSet('form_employee', 'Mona Adel')
        ->assertSet('form_day', 'wed')
        ->set('form_start', '12:00')
        ->set('form_end', '20:00')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/api/provider/shifts')
        && $req['employee'] === 'Mona Adel'
        && $req['day'] === 'wed'
        && $req['start'] === '12:00'
        && $req['end'] === '20:00');
});

it('optimistically adds a shift under fallback', function () {
    Http::fake(['*/api/provider/shifts*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Schedule::class)
        ->call('openCreate', 'سارة أحمد', 'fri')
        ->set('form_start', '10:00')
        ->set('form_end', '14:00')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertDispatched('notify')
        ->assertSee('10:00–14:00');
});
