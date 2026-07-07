<?php

declare(strict_types=1);

use App\Livewire\Transactions\SafeBalances;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('lists safe balances with total balance KPI', function () {
    Http::fake(['*/api/provider/transactions/safe-balances*' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'SB1', 'name' => 'خزنة المبيعات', 'branch' => 'الفرع الرئيسي', 'balance' => 500000, 'last_activity' => '2026-07-04 12:00:00', 'is_active' => true],
    ]])]);

    Livewire::test(SafeBalances::class)
        ->assertSee('خزنة المبيعات')
        ->assertSee('الفرع الرئيسي')
        ->assertSee('5,000 EGP'); // 500,000 minor -> total balance
});

it('falls back to sample safes when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(SafeBalances::class)
        ->assertSee('sample data')
        ->assertSee('الخزنة الرئيسية')
        ->assertSee('درج فرع المول');
});
