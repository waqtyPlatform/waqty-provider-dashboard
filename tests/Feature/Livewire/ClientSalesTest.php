<?php

declare(strict_types=1);

use App\Livewire\Transactions\ClientSales;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeClientSales(): void
{
    Http::fake(['*/api/provider/transactions/client-sales*' => Http::response(['success' => true, 'data' => [
        ['name' => 'Nadia', 'group' => 'vip', 'visits' => 9, 'total' => 300000, 'last_purchase' => '2026-07-01'],
        ['name' => 'Omar', 'group' => 'regular', 'visits' => 4, 'total' => 120000, 'last_purchase' => '2026-06-20'],
        ['name' => 'Salma', 'group' => 'new', 'visits' => 2, 'total' => 80000, 'last_purchase' => '2026-06-15'],
    ]])]);
}

it('lists per-client totals with revenue KPIs', function () {
    fakeClientSales();

    Livewire::test(ClientSales::class)
        ->assertSee('Nadia')
        ->assertSee('3,000 EGP'); // 300,000 minor -> total spent
});

it('searches by client name', function () {
    fakeClientSales();

    Livewire::test(ClientSales::class)
        ->set('search', 'Omar')
        ->assertSee('Omar')
        ->assertDontSee('Salma');
});

it('falls back to sample client sales when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(ClientSales::class)
        ->assertSee('sample data')
        ->assertSee('فاطمة رشاد');
});
