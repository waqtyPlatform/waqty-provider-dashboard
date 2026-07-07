<?php

declare(strict_types=1);

use App\Livewire\Transactions\AdvancePayments;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('lists advance payments with sample data when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(AdvancePayments::class)
        ->assertSee('sample data')
        ->assertSee('ADV-100234')
        ->assertSee('فاطمة رشاد');
});

it('validates the new advance form', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(AdvancePayments::class)
        ->call('openCreate')
        ->set('form_client', '')
        ->set('form_amount', '')
        ->call('save')
        ->assertHasErrors(['form_client', 'form_amount']);
});

it('creates an advance payment and notifies', function () {
    Http::fake([
        '*/api/provider/transactions/advance-payments*' => function ($request) {
            return $request->method() === 'POST'
                ? Http::response(['success' => true, 'data' => ['uuid' => 'NEW']], 200)
                : Http::response(['success' => true, 'data' => [
                    ['uuid' => 'A1', 'reference' => 'ADV-1', 'client' => 'عميل تجريبي', 'amount' => 10000, 'date' => '2026-07-01', 'status' => 'completed', 'applied_to' => null],
                ]]);
        },
    ]);

    Livewire::test(AdvancePayments::class)
        ->call('openCreate')
        ->set('form_client', 'نور محمد')
        ->set('form_amount', '250')
        ->set('form_method', 'card')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertDispatched('notify');

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/api/provider/transactions/advance-payments')
        && $req['amount'] === 25000
        && $req['client'] === 'نور محمد'
        && $req['payment_method'] === 'card');
});
