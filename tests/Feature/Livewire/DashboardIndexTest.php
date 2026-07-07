<?php

declare(strict_types=1);

use App\Livewire\Dashboard\Index;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeDashboard(): void
{
    Http::fake([
        '*/api/provider/dashboard/summary*' => Http::response(['success' => true, 'data' => [
            'total_revenue' => 5480000,
            'total_bookings' => 486,
            'new_clients' => 63,
            'total_invoices' => 402,
            'total_returns' => 7,
            'revenue_trend' => 12.4,
            'bookings_trend' => 8.1,
            'clients_trend' => -3.2,
            'top_services' => [['name' => 'Hair Color', 'revenue' => 1350000, 'count' => 90]],
            'top_clients' => [['name' => 'Mariam Adel', 'visits' => 41, 'spent' => 3120000]],
            'booking_status_distribution' => [['status' => 'completed', 'count' => 298]],
            'revenue_by_day' => [['date' => 'Jul 1', 'revenue' => 120000]],
            'occupancy_rate' => 74.5,
        ]]),
        '*/api/provider/dashboard' => Http::response(['success' => true, 'data' => [
            'bookings' => ['total' => 486, 'today' => ['total' => 18]],
            'revenue' => ['total' => 5480000, 'today' => 214000],
            'employees' => ['total' => 9, 'active' => 7, 'blocked' => 1],
            'branches' => ['total' => 2, 'active' => 2],
            'ratings' => ['total' => 214, 'average' => 4.7],
        ]]),
        '*/api/provider/bookings/next-upcoming' => Http::response(['success' => true, 'data' => [
            'user' => ['name' => 'Nour El-Din'],
            'service' => ['name' => 'Classic Haircut'],
            'start_time' => '14:30',
            'price' => 15000,
        ]]),
    ]);
}

it('renders KPIs and top lists from the summary API', function () {
    fakeDashboard();

    Livewire::test(Index::class)
        ->assertSee('486')          // total bookings
        ->assertSee('Hair Color')   // top service
        ->assertSee('Mariam Adel')  // top client
        ->assertSee('Nour El-Din'); // next appointment
});

it('exposes chart options built from the summary', function () {
    fakeDashboard();

    $component = Livewire::test(Index::class);
    $donut = $component->instance()->donutOptions();
    $revenue = $component->instance()->revenueOptions();

    expect($donut['series'])->toBe([298])
        ->and($donut['labels'])->toBe(['Completed'])
        ->and($revenue['series'][0]['data'])->toBe([1200.0]); // 120000 minor -> 1200 EGP
});

it('dispatches a chart-refresh event when the range changes', function () {
    fakeDashboard();

    Livewire::test(Index::class)
        ->set('range', '7d')
        ->assertDispatched('dash-charts');
});

it('falls back to sample data when the API is unavailable', function () {
    Http::fake(['*' => Http::response(['message' => 'Server error'], 500)]);

    Livewire::test(Index::class)
        ->assertSee('sample data')
        ->assertSee('صبغة شعر'); // fallback top service
});
