<?php

declare(strict_types=1);

use App\Livewire\Bookings\Payments;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakePaymentsList(): void
{
    Http::fake([
        '*/api/provider/bookings/*/payments' => Http::response(['success' => true, 'data' => ['uuid' => 'PNEW']], 200),
        '*/api/provider/payments/*' => Http::response(['success' => true, 'data' => ['uuid' => 'P1']], 200),
        '*/api/provider/payments*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'P1', 'booking_uuid' => 'BK1', 'booking' => ['uuid' => 'BK1', 'booking_date' => '2026-06-30', 'service' => ['name' => 'Hair Color']], 'payment_method' => 'paymob', 'amount' => 45000, 'status' => 'completed', 'transaction_id' => 'PMB-8842013', 'created_at' => '2026-06-30'],
            ['uuid' => 'P2', 'booking_uuid' => 'BK2', 'booking' => ['uuid' => 'BK2', 'booking_date' => '2026-06-29', 'service' => ['name' => 'Classic Haircut']], 'payment_method' => 'cash', 'amount' => 15000, 'status' => 'pending', 'transaction_id' => null, 'created_at' => '2026-06-29'],
        ]]),
    ]);
}

it('lists payments from the API', function () {
    fakePaymentsList();

    Livewire::test(Payments::class)
        ->assertSee('Hair Color')
        ->assertSee('PMB-8842013')
        ->assertSee('450 EGP');
});

it('filters payments by method', function () {
    fakePaymentsList();

    Livewire::test(Payments::class)
        ->set('methodFilter', 'cash')
        ->assertSee('Classic Haircut')
        ->assertDontSee('Hair Color');
});

it('records a payment via the API', function () {
    fakePaymentsList();

    Livewire::test(Payments::class)
        ->call('openCreate')
        ->assertSet('showForm', true)
        ->set('form_booking_uuid', 'BK9')
        ->set('form_amount', '250')
        ->set('form_payment_method', 'cash')
        ->set('form_status', 'completed')
        ->call('save')
        ->assertSet('showForm', false);

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/api/provider/bookings/BK9/payments')
        && $req['amount'] === 25000
        && $req['payment_method'] === 'cash'
        && $req['status'] === 'completed');
});

it('refunds a payment via the API', function () {
    fakePaymentsList();

    Livewire::test(Payments::class)
        ->call('refund', 'P1');

    Http::assertSent(fn ($req) => $req->method() === 'PUT'
        && str_contains($req->url(), '/api/provider/payments/P1')
        && $req['status'] === 'refunded');
});

it('validates the record-payment form', function () {
    fakePaymentsList();

    Livewire::test(Payments::class)
        ->call('openCreate')
        ->set('form_booking_uuid', '')
        ->set('form_amount', '')
        ->call('save')
        ->assertHasErrors(['form_booking_uuid' => 'required', 'form_amount' => 'required']);
});

it('falls back to sample data when the API is unavailable', function () {
    Http::fake([
        '*/api/provider/payments*' => Http::response(['message' => 'Server error'], 500),
    ]);

    Livewire::test(Payments::class)
        ->assertSee('sample data');
});
