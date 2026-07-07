<?php

declare(strict_types=1);

use App\Livewire\Transactions\PackageSales;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('lists package sales with session progress and revenue', function () {
    Http::fake(['*/api/provider/transactions/package-sales*' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'PS1', 'package_name' => 'Laser Package', 'client' => ['name' => 'Nour'], 'sessions_used' => 2, 'sessions_total' => 6, 'amount' => 200000, 'sold_at' => '2026-06-01', 'status' => 'active'],
        ['uuid' => 'PS2', 'package_name' => 'Facial Package', 'client' => ['name' => 'Salma'], 'sessions_used' => 4, 'sessions_total' => 4, 'amount' => 120000, 'sold_at' => '2026-05-01', 'status' => 'completed'],
    ]])]);

    Livewire::test(PackageSales::class)
        ->assertSee('Laser Package')
        ->assertSee('Nour')
        ->assertSee('2 / 6')
        ->assertSee('2,000 EGP'); // 200,000 minor
});

it('filters package sales by status', function () {
    Http::fake(['*/api/provider/transactions/package-sales*' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'PS1', 'package_name' => 'Laser Package', 'client' => ['name' => 'Nour'], 'sessions_used' => 2, 'sessions_total' => 6, 'amount' => 200000, 'sold_at' => '2026-06-01', 'status' => 'active'],
        ['uuid' => 'PS2', 'package_name' => 'Facial Package', 'client' => ['name' => 'Salma'], 'sessions_used' => 4, 'sessions_total' => 4, 'amount' => 120000, 'sold_at' => '2026-05-01', 'status' => 'completed'],
    ]])]);

    Livewire::test(PackageSales::class)
        ->set('statusFilter', 'completed')
        ->assertSee('Facial Package')
        ->assertDontSee('Laser Package');
});

it('falls back to sample package sales when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(PackageSales::class)
        ->assertSee('sample data')
        ->assertSee('فاطمة رشاد');
});
