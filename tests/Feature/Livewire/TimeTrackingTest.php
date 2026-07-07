<?php

declare(strict_types=1);

use App\Livewire\Employees\TimeTracking;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeTimeTracking(): void
{
    Http::fake([
        '*/api/provider/time-tracking*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'TT1', 'employee' => 'Sara Ahmed', 'date' => '2026-07-06', 'clock_in' => '09:00', 'clock_out' => '17:30', 'worked_minutes' => 510, 'overtime_minutes' => 30],
            ['uuid' => 'TT2', 'employee' => 'Khaled Hassan', 'date' => '2026-07-05', 'clock_in' => '09:10', 'clock_out' => '15:40', 'worked_minutes' => 390, 'overtime_minutes' => 0],
        ]]),
    ]);
}

it('renders time records from the API with formatted worked hours', function () {
    fakeTimeTracking();

    Livewire::test(TimeTracking::class)
        ->assertSee('Sara Ahmed')
        ->assertSee('Khaled Hassan')
        ->assertSee('8:30'); // 510 worked minutes
});

it('falls back to Arabic sample records when the API is unavailable', function () {
    Http::fake(['*/api/provider/time-tracking*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(TimeTracking::class)
        ->assertSee('sample data')
        ->assertSee('سارة أحمد')
        ->assertSee('طارق سامي');
});

it('filters records by employee', function () {
    fakeTimeTracking();

    // Assert on each row's unique worked-hours cell (the employee name also
    // appears in the filter <select>, so it is not a reliable row marker).
    Livewire::test(TimeTracking::class)
        ->set('employeeFilter', 'Sara Ahmed')
        ->assertSee('8:30')  // Sara's worked hours (510 min)
        ->assertDontSee('6:30'); // Khaled's worked hours (390 min) — filtered out
});

it('validates the manual entry form', function () {
    fakeTimeTracking();

    Livewire::test(TimeTracking::class)
        ->call('openCreate')
        ->set('form_employee', '')
        ->set('form_clock_in', '')
        ->set('form_clock_out', '')
        ->call('save')
        ->assertHasErrors(['form_employee' => 'required', 'form_clock_in' => 'required', 'form_clock_out' => 'required'])
        ->assertSet('showForm', true);
});

it('creates a time entry, notifies, and sends worked minutes', function () {
    fakeTimeTracking();

    Livewire::test(TimeTracking::class)
        ->call('openCreate')
        ->set('form_employee', 'Mona Adel')
        ->set('form_date', '2026-07-06')
        ->set('form_clock_in', '09:00')
        ->set('form_clock_out', '17:30')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/api/provider/time-tracking')
        && $req['employee'] === 'Mona Adel'
        && $req['worked_minutes'] === 510
        && $req['overtime_minutes'] === 30);
});

it('rejects a clock-out that is not after clock-in', function () {
    fakeTimeTracking();

    Livewire::test(TimeTracking::class)
        ->call('openCreate')
        ->set('form_employee', 'Mona Adel')
        ->set('form_date', '2026-07-06')
        ->set('form_clock_in', '17:00')
        ->set('form_clock_out', '09:00')
        ->call('save')
        ->assertHasErrors('form_clock_out')
        ->assertSet('showForm', true);
});

it('exports and notifies', function () {
    fakeTimeTracking();

    Livewire::test(TimeTracking::class)
        ->call('export')
        ->assertDispatched('notify');
});
