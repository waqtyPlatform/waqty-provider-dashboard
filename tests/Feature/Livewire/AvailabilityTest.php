<?php

declare(strict_types=1);

use App\Livewire\Employees\Availability;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeAvailability(): void
{
    Http::fake([
        '*/api/provider/availability*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'AV1', 'employee' => 'Sara Ahmed', 'branch' => 'Downtown', 'status' => 'available', 'slots' => [
                ['day' => 'sun', 'from' => '10:00', 'to' => '18:00'],
                ['day' => 'mon', 'from' => '10:00', 'to' => '18:00'],
            ]],
            ['uuid' => 'AV2', 'employee' => 'Khaled Hassan', 'branch' => 'New Cairo', 'status' => 'on_leave', 'slots' => [
                ['day' => 'mon', 'from' => '09:00', 'to' => '17:00'],
            ]],
        ]]),
    ]);
}

it('renders employee availability from the API with weekly slots', function () {
    fakeAvailability();

    Livewire::test(Availability::class)
        ->assertSee('Sara Ahmed')
        ->assertSee('Khaled Hassan')
        ->assertSee('10:00–18:00');
});

it('falls back to Arabic sample availability when the API is unavailable', function () {
    Http::fake(['*/api/provider/availability*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Availability::class)
        ->assertSee('sample data')
        ->assertSee('سارة أحمد')
        ->assertSee('طارق سامي');
});

it('filters availability by branch', function () {
    fakeAvailability();

    // Slot times are unique per card (18:00 = Sara, 09:00 = Khaled); the
    // filter selects still list every name, so assert on the cards themselves.
    Livewire::test(Availability::class)
        ->set('branchFilter', 'Downtown')
        ->assertSee('18:00')
        ->assertDontSee('09:00');
});

it('filters availability by employee', function () {
    fakeAvailability();

    Livewire::test(Availability::class)
        ->set('employeeFilter', 'Khaled Hassan')
        ->assertSee('09:00')
        ->assertDontSee('18:00');
});
