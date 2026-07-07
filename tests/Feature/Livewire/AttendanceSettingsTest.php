<?php

declare(strict_types=1);

use App\Livewire\Employees\AttendanceSettings;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeAttendanceSettings(): void
{
    Http::fake([
        '*/api/provider/settings/attendance' => Http::response(['success' => true, 'data' => [
            'shift_start' => '08:30',
            'shift_end' => '16:30',
            'late_threshold' => 20,
            'early_leave_threshold' => 25,
            'overtime_multiplier' => 1.75,
            'grace_period' => 5,
            'auto_absent_after' => 90,
        ]]),
    ]);
}

it('renders the attendance settings form from the API', function () {
    fakeAttendanceSettings();

    Livewire::test(AttendanceSettings::class)
        ->assertOk()
        ->assertSet('fallbackUsed', false)
        ->assertSet('form_shift_start', '08:30')
        ->assertSet('form_late_threshold', '20')
        ->assertSet('form_overtime_multiplier', '1.75');
});

it('falls back to sample settings when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(AttendanceSettings::class)
        ->assertOk()
        ->assertSee('sample data')
        ->assertSet('fallbackUsed', true)
        ->assertSet('form_shift_start', '09:00')
        ->assertSet('form_auto_absent_after', '120');
});

it('shows the Arabic sample-data notice in Arabic locale', function () {
    app()->setLocale('ar');
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(AttendanceSettings::class)
        ->assertOk()
        ->assertSet('fallbackUsed', true)
        ->assertSee('بيانات توضيحية');
});

it('validates required policy fields on save', function () {
    fakeAttendanceSettings();

    Livewire::test(AttendanceSettings::class)
        ->set('form_shift_start', '')
        ->set('form_late_threshold', '')
        ->call('save')
        ->assertHasErrors(['form_shift_start', 'form_late_threshold'])
        ->assertNotDispatched('notify');
});

it('saves the attendance settings and notifies', function () {
    fakeAttendanceSettings();

    Livewire::test(AttendanceSettings::class)
        ->set('form_late_threshold', '30')
        ->set('form_overtime_multiplier', '2')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    Http::assertSent(fn ($req) => $req->method() === 'PATCH'
        && str_contains($req->url(), '/api/provider/settings/attendance')
        && (int) $req['late_threshold'] === 30
        && (float) $req['overtime_multiplier'] === 2.0);
});
