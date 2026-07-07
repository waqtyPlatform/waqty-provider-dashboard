<?php

declare(strict_types=1);

use App\Livewire\Customers\ClientAccounts;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeClients(): void
{
    Http::fake([
        '*/api/provider/clients/A1/bookings*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'BK1', 'service' => ['name' => 'صبغة شعر'], 'booking_date' => '2026-06-28', 'start_time' => '09:00:00', 'status' => 'completed', 'price' => 45000],
        ]]),
        '*/api/provider/clients*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'A1', 'name' => 'آية نبيل', 'email' => 'alice@x.com', 'phone' => '01000000000', 'total_bookings' => 12, 'last_booking_date' => '2026-06-28'],
            ['uuid' => 'A2', 'name' => 'باسم سمير', 'email' => 'bob@x.com', 'phone' => '01111111111', 'total_bookings' => 3, 'last_booking_date' => '2026-05-01'],
        ]]),
    ]);
}

it('lists client accounts from the API with KPIs', function () {
    fakeClients();

    Livewire::test(ClientAccounts::class)
        ->assertSee('آية نبيل')
        ->assertSee('باسم سمير')
        ->assertSee('12'); // total bookings for Alice
});

it('filters client accounts by search', function () {
    fakeClients();

    Livewire::test(ClientAccounts::class)
        ->set('search', 'alice')
        ->assertSee('آية نبيل')
        ->assertDontSee('باسم سمير');
});

it('opens booking history and loads the client bookings', function () {
    fakeClients();

    Livewire::test(ClientAccounts::class)
        ->call('openHistory', 'A1')
        ->assertSet('showHistory', true)
        ->assertSet('historyName', 'آية نبيل')
        ->assertSee('صبغة شعر');
});

it('falls back to sample clients when the API is unavailable', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(ClientAccounts::class)
        ->assertSee('sample data')
        ->assertSee('ليلى حسن');
});
