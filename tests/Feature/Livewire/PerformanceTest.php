<?php

declare(strict_types=1);

use App\Livewire\Employees\Performance;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakePerformance(): void
{
    Http::fake([
        '*/api/provider/employee-performance*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'P1', 'employee' => 'Khaled Hassan', 'bookings' => 90, 'revenue' => 3000000, 'rating' => 4.2, 'utilization' => 65],
            ['uuid' => 'P2', 'employee' => 'Sara Ahmed', 'bookings' => 120, 'revenue' => 5000000, 'rating' => 4.8, 'utilization' => 88],
        ]]),
    ]);
}

it('renders the ranking from the API with derived totals and average rating', function () {
    fakePerformance();

    Livewire::test(Performance::class)
        ->assertSee('Sara Ahmed')
        ->assertSee('Khaled Hassan')
        ->assertSee('50,000') // Sara's revenue: 5,000,000 minor units = 50,000 EGP
        ->assertSee('4.5'); // average rating: (4.8 + 4.2) / 2
});

it('falls back to Arabic sample ranking when the API is unavailable', function () {
    Http::fake(['*/api/provider/employee-performance*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Performance::class)
        ->assertSee('sample data')
        ->assertSee('سارة أحمد')
        ->assertSee('عمر نبيل');
});

it('reloads the ranking for the selected period', function () {
    fakePerformance();

    Livewire::test(Performance::class)
        ->assertSet('period', 'month')
        ->set('period', 'year')
        ->assertSee('Sara Ahmed');

    Http::assertSent(fn ($req) => str_contains($req->url(), 'period=year'));
});
