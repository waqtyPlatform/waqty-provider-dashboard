<?php

declare(strict_types=1);

use App\Livewire\Bookings\PrintSchedule;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakePrintSchedule(): void
{
    Http::fake([
        '*/api/provider/employees*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'E1', 'name' => 'Sara Ahmed', 'active' => true, 'blocked' => false, 'position' => 'Owner'],
            ['uuid' => 'E3', 'name' => 'Khaled Hassan', 'active' => true, 'blocked' => false],
        ]]),
        '*/api/provider/bookings*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'BK1', 'employee_uuid' => 'E1', 'user' => ['name' => 'Fatima'], 'service' => ['name' => 'Hair Color'], 'booking_date' => '2026-07-03', 'start_time' => '11:00:00', 'end_time' => '12:00:00', 'status' => 'confirmed', 'price' => 40000],
            ['uuid' => 'BK2', 'employee_uuid' => 'E3', 'user' => ['name' => 'Omar'], 'service' => ['name' => 'Haircut'], 'booking_date' => '2026-07-03', 'start_time' => '09:30:00', 'end_time' => '10:00:00', 'status' => 'completed', 'price' => 15000],
            ['uuid' => 'BK3', 'employee_uuid' => 'E1', 'user' => ['name' => 'Nour'], 'service' => ['name' => 'Facial'], 'booking_date' => '2026-07-03', 'start_time' => '09:00:00', 'end_time' => '10:30:00', 'status' => 'confirmed', 'price' => 45000],
        ]]),
    ]);
}

it('renders the daily schedule grouped by employee', function () {
    fakePrintSchedule();

    Livewire::test(PrintSchedule::class, ['date' => '2026-07-03'])
        ->assertSee('Sara Ahmed')
        ->assertSee('Khaled Hassan')
        ->assertSee('Fatima')
        ->assertSee('Hair Color')
        ->assertSee('Omar');
});

it("groups each employee's bookings sorted by time", function () {
    fakePrintSchedule();

    $groups = Livewire::test(PrintSchedule::class, ['date' => '2026-07-03'])->instance()->schedule();

    // Sara (E1) owns BK1 + BK3; the earlier 09:00 (BK3) must sort first.
    $sara = collect($groups)->first(fn ($g) => $g['employee']?->uuid === 'E1');
    expect($sara['count'])->toBe(2)
        ->and($sara['bookings'][0]->uuid)->toBe('BK3')
        ->and($sara['bookings'][1]->uuid)->toBe('BK1')
        ->and($sara['revenue'])->toBe(85000);

    $khaled = collect($groups)->first(fn ($g) => $g['employee']?->uuid === 'E3');
    expect($khaled['count'])->toBe(1)
        ->and($khaled['bookings'][0]->uuid)->toBe('BK2');
});

it('filters the sheet down to a single employee', function () {
    fakePrintSchedule();

    $groups = Livewire::test(PrintSchedule::class, ['date' => '2026-07-03'])
        ->set('employeeFilter', 'E3')
        ->instance()->schedule();

    expect($groups)->toHaveCount(1)
        ->and($groups[0]['employee']->uuid)->toBe('E3');
});

it('falls back to sample bookings when the API is unavailable', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(PrintSchedule::class, ['date' => '2026-07-03'])
        ->assertSee('sample data')
        ->assertSee('فاطمة رشاد'); // sample booking client
});
