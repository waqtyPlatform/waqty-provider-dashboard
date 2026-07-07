<?php

declare(strict_types=1);

use App\Livewire\Transactions\Dailies;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('lists the daily summary with sales and net totals', function () {
    Http::fake(['*/api/provider/transactions/dailies*' => Http::response(['success' => true, 'data' => [
        ['date' => '2026-07-04', 'sales' => 320000, 'refunds' => 15000, 'expenses' => 45000],
        ['date' => '2026-07-03', 'sales' => 180000, 'refunds' => 0, 'expenses' => 20000],
    ]])]);

    Livewire::test(Dailies::class)
        ->assertSee('3,200 EGP')   // day sales (320000 minor)
        ->assertSee('2,600 EGP')   // day net (320000 - 15000 - 45000)
        ->assertDontSee('sample data');
});

it('falls back to sample dailies when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Dailies::class)
        ->assertSee('sample data')
        ->assertSee('5,120 EGP'); // best-day sample sales (512000 minor)
});
