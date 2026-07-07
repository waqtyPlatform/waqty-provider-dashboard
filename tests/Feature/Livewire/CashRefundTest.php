<?php

declare(strict_types=1);

use App\Livewire\Returns\CashRefund;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('renders step one with the Arabic sample sales', function () {
    Livewire::test(CashRefund::class)
        ->assertSet('step', 1)
        ->assertSet('done', false)
        ->assertSee('فاطمة رشاد')
        ->assertSee('مريم سمير');
});

it('requires a sale before advancing from step one', function () {
    Livewire::test(CashRefund::class)
        ->call('next')
        ->assertHasErrors('transactionUuid')
        ->assertSet('step', 1);
});

it('preselects every line item when a sale is picked', function () {
    Livewire::test(CashRefund::class)
        ->call('selectTransaction', 'TXN-4821')
        ->assertSet('transactionUuid', 'TXN-4821')
        ->assertSet('selectedItems', ['TXN-4821-1', 'TXN-4821-2', 'TXN-4821-3']);
});

it('advances through item selection into the confirmation step', function () {
    Livewire::test(CashRefund::class)
        ->call('selectTransaction', 'TXN-4821')
        ->call('next')
        ->assertSet('step', 2)
        ->call('next')
        ->assertSet('step', 3);
});

it('rejects a refund amount greater than the original', function () {
    Livewire::test(CashRefund::class)
        ->call('selectTransaction', 'TXN-4821')
        ->call('next')
        ->set('itemAmounts.TXN-4821-1', '9999')
        ->call('next')
        ->assertHasErrors('itemAmounts.TXN-4821-1')
        ->assertSet('step', 2);
});

it('requires a reason before submitting', function () {
    Livewire::test(CashRefund::class)
        ->call('selectTransaction', 'TXN-4821')
        ->set('step', 3)
        ->call('submit')
        ->assertHasErrors('reason')
        ->assertSet('done', false);
});

it('submits the cash refund and reaches the done state', function () {
    Http::fake(['*/api/provider/returns' => Http::response(['success' => true, 'data' => [
        'uuid' => 'RT-NEW', 'type' => 'cash_refund', 'amount' => 68000, 'status' => 'pending',
    ]], 200)]);

    Livewire::test(CashRefund::class)
        ->call('selectTransaction', 'TXN-4821')
        ->set('step', 3)
        ->set('reason', 'notSatisfied')
        ->set('notes', 'استرداد كامل')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertDispatched('notify')
        ->assertSet('done', true);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/api/provider/returns')
        && $request['type'] === 'cash_refund'
        && $request['transaction_uuid'] === 'TXN-4821');
});

it('still completes when the Waqty API is unreachable', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(CashRefund::class)
        ->call('selectTransaction', 'TXN-4790')
        ->set('step', 3)
        ->set('reason', 'billingError')
        ->call('submit')
        ->assertDispatched('notify')
        ->assertSet('done', true);
});

it('resets the wizard when starting another refund', function () {
    Http::fake(['*' => Http::response(['success' => true, 'data' => []], 200)]);

    Livewire::test(CashRefund::class)
        ->call('selectTransaction', 'TXN-4821')
        ->set('step', 3)
        ->set('reason', 'doubleCharge')
        ->call('submit')
        ->assertSet('done', true)
        ->call('startOver')
        ->assertSet('done', false)
        ->assertSet('step', 1)
        ->assertSet('transactionUuid', null)
        ->assertSet('reason', '');
});
