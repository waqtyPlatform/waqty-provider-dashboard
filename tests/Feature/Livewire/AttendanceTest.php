<?php

declare(strict_types=1);

use App\Livewire\Employees\Attendance;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeAttendance(): void
{
    Http::fake([
        '*/api/provider/attendance/add-manual' => Http::response(['success' => true, 'data' => ['uuid' => 'NEW']], 200),
        '*/api/provider/attendance/*' => Http::response(['success' => true], 200),
        '*/api/provider/attendance*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'AT1', 'employee' => 'Sara Ahmed', 'date' => '2026-07-07', 'check_in' => '08:58', 'check_out' => '17:05', 'status' => 'present'],
            ['uuid' => 'AT2', 'employee' => 'Khaled Hassan', 'date' => '2026-07-06', 'check_in' => '09:35', 'check_out' => '17:20', 'status' => 'late'],
        ]]),
    ]);
}

it('lists attendance records from the API', function () {
    fakeAttendance();

    Livewire::test(Attendance::class)
        ->assertSee('Sara Ahmed')
        ->assertSee('Khaled Hassan')
        ->assertSee('08:58');
});

it('falls back to Arabic sample attendance when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Attendance::class)
        ->assertSee('sample data')
        ->assertSee('سارة أحمد')
        ->assertSee('ياسمين فاروق');
});

it('filters attendance by employee', function () {
    fakeAttendance();

    // Assert on unique check-in times: Sara 08:58 stays, Khaled 09:35 is filtered
    // out of the table (his name still appears as an option in the employee select).
    Livewire::test(Attendance::class)
        ->set('employeeFilter', 'Sara Ahmed')
        ->assertSee('08:58')
        ->assertDontSee('09:35');
});

it('filters attendance by date range', function () {
    fakeAttendance();

    Livewire::test(Attendance::class)
        ->set('dateFrom', '2026-07-07')
        ->assertSee('08:58')
        ->assertDontSee('09:35');
});

it('validates the manual attendance form', function () {
    fakeAttendance();

    Livewire::test(Attendance::class)
        ->call('openCreate')
        ->set('form_employee', '')
        ->set('form_date', '')
        ->call('save')
        ->assertHasErrors(['form_employee', 'form_date'])
        ->assertSet('showForm', true);
});

it('records a manual attendance entry and notifies', function () {
    fakeAttendance();

    Livewire::test(Attendance::class)
        ->call('openCreate')
        ->set('form_employee', 'سارة أحمد')
        ->set('form_date', '2026-07-07')
        ->set('form_check_in', '09:00')
        ->set('form_check_out', '17:00')
        ->set('form_status', 'present')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/api/provider/attendance/add-manual')
        && $req['employee'] === 'سارة أحمد'
        && $req['status'] === 'present');
});

it('exports attendance with a success notification', function () {
    fakeAttendance();

    Livewire::test(Attendance::class)
        ->call('exportAttendance')
        ->assertDispatched('notify');
});
