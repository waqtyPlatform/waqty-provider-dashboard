<?php

declare(strict_types=1);

use App\Livewire\Portal\AttendanceHistory;
use App\Livewire\Portal\Dashboard;
use App\Livewire\Portal\ShiftsSchedule;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('shows today bookings on the portal dashboard', function () {
    Http::fake([
        '*/api/employee/bookings*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'B1', 'start_time' => '09:30', 'service' => 'قصّة شعر كلاسيك', 'client' => 'أحمد', 'status' => 'completed'],
            ['uuid' => 'B2', 'start_time' => '11:00', 'service' => 'صبغة شعر', 'client' => 'نور', 'status' => 'confirmed'],
        ]]),
        '*/api/employee/attendance*' => Http::response(['success' => true, 'data' => [
            ['check_in' => '09:00', 'check_out' => null],
        ]]),
    ]);

    Livewire::test(Dashboard::class)
        ->assertSee('قصّة شعر كلاسيك')
        ->assertSee('صبغة شعر')
        ->assertSet('checkedIn', true);
});

it('checks in via the employee API', function () {
    Http::fake([
        '*/api/employee/attendance/check-in' => Http::response(['success' => true]),
        '*/api/employee/bookings*' => Http::response(['success' => true, 'data' => []]),
        '*/api/employee/attendance*' => Http::response(['success' => true, 'data' => []]),
    ]);

    Livewire::test(Dashboard::class)
        ->assertSet('checkedIn', false)
        ->call('checkIn')
        ->assertSet('checkedIn', true);

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/api/employee/attendance/check-in'));
});

it('falls back to sample bookings when offline', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Dashboard::class)
        ->assertSet('fallbackUsed', true)
        ->assertSee('sample data')
        ->assertSee('قصّة شعر كلاسيك');
});

it('loads attendance records for the month', function () {
    Http::fake([
        '*/api/employee/attendance*' => Http::response(['success' => true, 'data' => [
            ['date' => '2026-07-01', 'status' => 'present', 'check_in' => '08:55', 'check_out' => '17:00', 'worked_minutes' => 485],
            ['date' => '2026-07-02', 'status' => 'late', 'check_in' => '09:40', 'check_out' => '17:00', 'worked_minutes' => 440],
        ]]),
    ]);

    $component = Livewire::test(AttendanceHistory::class)
        ->assertSee('2026-07-01');

    expect($component->get('records'))->toHaveCount(2);
    $stats = $component->instance()->stats();
    expect($stats['present'])->toBe(1)->and($stats['late'])->toBe(1);
});

it('navigates attendance months', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    $component = Livewire::test(AttendanceHistory::class)->assertSet('fallbackUsed', true);
    $start = $component->get('month');
    $component->call('prevMonth');
    expect($component->get('month'))->not->toBe($start);
});

it('lists shifts for the current month with fallback', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    $component = Livewire::test(ShiftsSchedule::class)
        ->assertSet('fallbackUsed', true)
        ->assertSee('sample data');

    expect(count($component->get('shifts')))->toBeGreaterThan(0);
});
