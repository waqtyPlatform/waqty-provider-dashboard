<?php

declare(strict_types=1);

use App\Livewire\Expenses\Index as Expenses;
use App\Livewire\Returns\Index as Returns;
use App\Livewire\Transactions\Index as Transactions;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

// ── Transactions ────────────────────────────────────────────
it('lists transactions with sales/refund KPIs', function () {
    Http::fake(['*/api/provider/transactions*' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'T1', 'type' => 'sale', 'amount' => 45000, 'payment_method' => 'card', 'status' => 'completed', 'customer' => ['name' => 'Layla'], 'reference_number' => 'TXN-1'],
        ['uuid' => 'T2', 'type' => 'refund', 'amount' => 15000, 'payment_method' => 'cash', 'status' => 'completed', 'customer' => ['name' => 'Omar'], 'reference_number' => 'TXN-2'],
    ]])]);

    Livewire::test(Transactions::class)
        ->assertSee('TXN-1')
        ->assertSee('Layla')
        ->assertSee('450 EGP'); // sale amount
});

it('filters transactions by type', function () {
    Http::fake(['*/api/provider/transactions*' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'T1', 'type' => 'sale', 'amount' => 45000, 'status' => 'completed', 'customer' => ['name' => 'Layla'], 'reference_number' => 'TXN-1'],
        ['uuid' => 'T2', 'type' => 'refund', 'amount' => 15000, 'status' => 'completed', 'customer' => ['name' => 'Omar'], 'reference_number' => 'TXN-2'],
    ]])]);

    Livewire::test(Transactions::class)
        ->set('typeFilter', 'refund')
        ->assertSee('TXN-2')
        ->assertDontSee('TXN-1');
});

it('falls back to sample transactions when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Transactions::class)
        ->assertSee('sample data')
        ->assertSee('TXN-100234');
});

// ── Expenses ────────────────────────────────────────────────
function fakeExpenses(): void
{
    Http::fake([
        '*/api/provider/expenses/*/approve' => Http::response(['success' => true], 200),
        '*/api/provider/expenses' => Http::response(['success' => true, 'data' => ['uuid' => 'NEW']], 200),
        '*/api/provider/expenses*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'X1', 'category' => 'Rent', 'vendor' => 'Cairo Properties', 'description' => 'Monthly rent', 'amount' => 1500000, 'status' => 'approved', 'date' => '2026-07-01'],
            ['uuid' => 'X2', 'category' => 'Utilities', 'vendor' => 'Electricity', 'description' => 'Power bill', 'amount' => 180000, 'status' => 'pending', 'date' => '2026-06-25'],
        ]]),
    ]);
}

it('lists expenses with money KPIs', function () {
    fakeExpenses();

    Livewire::test(Expenses::class)
        ->assertSee('Monthly rent')
        ->assertSee('15,000 EGP'); // 1,500,000 minor
});

it('validates and records an expense', function () {
    fakeExpenses();

    Livewire::test(Expenses::class)
        ->call('openCreate')
        ->set('form_description', '')
        ->set('form_amount', '')
        ->call('save')
        ->assertHasErrors(['form_description', 'form_amount']);

    Livewire::test(Expenses::class)
        ->call('openCreate')
        ->set('form_description', 'Coffee supplies')
        ->set('form_amount', '250')
        ->call('save')
        ->assertSet('showForm', false);

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/api/provider/expenses')
        && $req['amount'] === 25000  // 250 EGP -> minor
        && $req['description'] === 'Coffee supplies');
});

it('approves a pending expense via the API', function () {
    fakeExpenses();

    Livewire::test(Expenses::class)
        ->call('approve', 'X2')
        ->assertSet('overrides.X2', 'approved');

    Http::assertSent(fn ($req) => $req->method() === 'PATCH'
        && str_contains($req->url(), '/api/provider/expenses/X2/approve'));
});

// ── Returns ─────────────────────────────────────────────────
function fakeReturns(): void
{
    Http::fake([
        '*/api/provider/returns/*/approve' => Http::response(['success' => true], 200),
        '*/api/provider/returns/*/reject' => Http::response(['success' => true], 200),
        '*/api/provider/returns*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'RT1', 'type' => 'cash_refund', 'customer' => ['name' => 'Youssef'], 'amount' => 15000, 'reason' => 'Service issue', 'status' => 'pending'],
            ['uuid' => 'RT2', 'type' => 'cash_refund', 'customer' => ['name' => 'Omar'], 'amount' => 8000, 'reason' => 'Double charge', 'status' => 'approved'],
        ]]),
    ]);
}

it('lists returns with status KPIs', function () {
    fakeReturns();

    Livewire::test(Returns::class)
        ->assertSee('Youssef')
        ->assertSee('Service issue');
});

it('approves a return via the API', function () {
    fakeReturns();

    Livewire::test(Returns::class)
        ->call('approve', 'RT1')
        ->assertSet('overrides.RT1', 'approved');

    Http::assertSent(fn ($req) => $req->method() === 'PATCH'
        && str_contains($req->url(), '/api/provider/returns/RT1/approve'));
});

it('rejects a return with a reason', function () {
    fakeReturns();

    Livewire::test(Returns::class)
        ->call('openReject', 'RT1')
        ->set('rejectReason', 'Outside refund window')
        ->call('submitReject')
        ->assertSet('showReject', false)
        ->assertSet('overrides.RT1', 'rejected');

    Http::assertSent(fn ($req) => $req->method() === 'PATCH'
        && str_contains($req->url(), '/api/provider/returns/RT1/reject')
        && $req['reason'] === 'Outside refund window');
});

it('falls back to sample returns when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Returns::class)
        ->assertSee('sample data')
        ->assertSee('تم إلغاء الحجز من قبل العميل');
});
