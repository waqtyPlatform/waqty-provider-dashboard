<?php

declare(strict_types=1);

use App\Livewire\Transactions\CashSales;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('lists cash sales from the API with money KPIs', function () {
    Http::fake(['*/api/provider/transactions/cash-sales*' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'CS1', 'receipt_number' => 'REC-9001', 'created_at' => '2026-07-04 11:20:00', 'client' => 'Nadia', 'services' => ['Haircut'], 'employee' => 'Sara', 'payment_method' => 'cash', 'amount' => 32000],
        ['uuid' => 'CS2', 'receipt_number' => 'REC-9002', 'created_at' => '2026-07-04 10:05:00', 'client' => 'Omar', 'services' => ['Manicure'], 'employee' => 'Yasmin', 'payment_method' => 'card', 'amount' => 18000],
    ]])]);

    Livewire::test(CashSales::class)
        ->assertSee('REC-9001')
        ->assertSee('Nadia')
        ->assertSee('500 EGP'); // total cash collected (32000 + 18000 minor)
});

it('falls back to Arabic sample cash sales when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(CashSales::class)
        ->assertSee('sample data')
        ->assertSee('فاطمة رشاد')
        ->assertSee('REC-2041');
});

it('searches cash sales by client name', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(CashSales::class)
        ->set('search', 'سلمى')
        ->assertSee('REC-2045')
        ->assertDontSee('REC-2041');
});
