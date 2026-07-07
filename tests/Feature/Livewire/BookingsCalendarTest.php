<?php

declare(strict_types=1);

use App\Livewire\Bookings\Calendar;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeCalendar(): void
{
    Http::fake([
        '*/api/provider/employees*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'E1', 'name' => 'Sara Ahmed', 'active' => true, 'blocked' => false],
            ['uuid' => 'E3', 'name' => 'Khaled Hassan', 'active' => true, 'blocked' => false],
        ]]),
        '*/api/provider/bookings*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'BK1', 'employee_uuid' => 'E1', 'user' => ['name' => 'Fatima'], 'service' => ['name' => 'Hair Color'], 'booking_date' => '2026-07-03', 'start_time' => '09:00:00', 'end_time' => '10:30:00', 'status' => 'confirmed', 'price' => 45000],
            ['uuid' => 'BK2', 'employee_uuid' => 'E3', 'user' => ['name' => 'Omar'], 'service' => ['name' => 'Haircut'], 'booking_date' => '2026-07-03', 'start_time' => '09:30:00', 'end_time' => '10:00:00', 'status' => 'completed', 'price' => 15000],
            ['uuid' => 'BK3', 'employee_uuid' => 'E1', 'user' => ['name' => 'Nour'], 'service' => ['name' => 'Facial'], 'booking_date' => '2026-07-03', 'start_time' => '11:00:00', 'end_time' => '12:00:00', 'status' => 'cancelled', 'price' => 40000],
        ]]),
    ]);
}

it('positions bookings into employee-column blocks', function () {
    fakeCalendar();

    $blocks = Livewire::test(Calendar::class, ['date' => '2026-07-03'])->instance()->blocks();

    expect($blocks)->toHaveCount(3);

    $bk1 = collect($blocks)->firstWhere('uuid', 'BK1');
    expect($bk1['empIndex'])->toBe(0)   // Sara = first column
        ->and($bk1['startSlot'])->toBe(0)   // 09:00 = slot 0
        ->and($bk1['span'])->toBe(3);       // 90 min / 30 = 3 slots

    $bk2 = collect($blocks)->firstWhere('uuid', 'BK2');
    expect($bk2['empIndex'])->toBe(1)   // Khaled = second column
        ->and($bk2['startSlot'])->toBe(1)   // 09:30 = slot 1
        ->and($bk2['span'])->toBe(1);
});

it('numbers the daily queue per employee, excluding cancelled', function () {
    fakeCalendar();

    $component = Livewire::test(Calendar::class, ['date' => '2026-07-03'])->instance();
    $queue = $component->queueMap();

    // BK1 is Sara's only non-cancelled booking -> #1; BK3 (cancelled) is excluded.
    expect($queue)->toHaveKey('BK1', 1)
        ->and($queue)->not->toHaveKey('BK3')
        ->and($queue)->toHaveKey('BK2', 1);
});

it('filters calendar blocks by status', function () {
    fakeCalendar();

    $blocks = Livewire::test(Calendar::class, ['date' => '2026-07-03'])
        ->set('statusFilter', 'completed')
        ->instance()->blocks();

    expect($blocks)->toHaveCount(1)
        ->and($blocks[0]['uuid'])->toBe('BK2');
});

it('advances the day with next/prev', function () {
    fakeCalendar();

    Livewire::test(Calendar::class, ['date' => '2026-07-03'])
        ->call('next')
        ->assertSet('date', '2026-07-04')
        ->call('prev')
        ->call('prev')
        ->assertSet('date', '2026-07-02');
});

it('navigates by week and month when the view changes', function () {
    fakeCalendar();

    Livewire::test(Calendar::class, ['date' => '2026-07-15'])
        ->set('view', 'week')
        ->call('next')
        ->assertSet('date', '2026-07-22')
        ->set('view', 'month')
        ->call('next')
        ->assertSet('date', '2026-08-22')
        ->call('prev')
        ->assertSet('date', '2026-07-22');
});

it('groups range bookings by date for the month view', function () {
    fakeCalendar();

    $component = Livewire::test(Calendar::class, ['date' => '2026-07-03'])
        ->set('view', 'month');

    expect($component->instance()->rangeBookings)->toBeArray();
});

it('falls back to sample bookings when the API is unavailable', function () {
    Http::fake(['*' => Http::response(['message' => 'Server error'], 500)]);

    Livewire::test(Calendar::class, ['date' => '2026-07-03'])
        ->assertSee('sample data')
        ->assertSee('فاطمة رشاد'); // sample booking client
});
