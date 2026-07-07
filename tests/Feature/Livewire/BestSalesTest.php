<?php

declare(strict_types=1);

use App\Livewire\Transactions\BestSales;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('falls back to sample best-sellers by service when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(BestSales::class)
        ->assertSee('sample data')
        ->assertSee('صبغة شعر')
        ->assertSee('8,520 EGP'); // 852,000 minor units -> top service revenue
});

it('switches the leaderboard from services to employees', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(BestSales::class)
        ->call('setView', 'employee')
        ->assertSet('view', 'employee')
        ->assertSee('سارة أحمد')
        ->assertDontSee('صبغة شعر');
});

it('renders best-sellers from the API when available', function () {
    Http::fake(['*/api/provider/transactions/best-sales*' => Http::response(['success' => true, 'data' => [
        ['name' => 'Highlights', 'count' => 12, 'revenue' => 50000],
    ]])]);

    Livewire::test(BestSales::class)
        ->assertSee('Highlights')
        ->assertSee('500 EGP') // 50,000 minor units
        ->assertDontSee('sample data');
});
