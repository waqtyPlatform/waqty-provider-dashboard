<?php

declare(strict_types=1);

use App\Livewire\Returns\PettyCashRefund;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('renders step one with the Arabic petty-cash entries', function () {
    Livewire::test(PettyCashRefund::class)
        ->assertSet('step', 1)
        ->assertSet('done', false)
        ->assertSee('مستلزمات نظافة')
        ->assertSee('فاطمة رشاد');
});

it('requires an entry before leaving step one', function () {
    Livewire::test(PettyCashRefund::class)
        ->call('next')
        ->assertHasErrors('pettyCashUuid')
        ->assertSet('step', 1);
});

it('advances through the steps once an entry and reason are chosen', function () {
    Livewire::test(PettyCashRefund::class)
        ->call('selectEntry', 'PC1')
        ->assertSet('pettyCashUuid', 'PC1')
        ->call('next')
        ->assertHasNoErrors()
        ->assertSet('step', 2)
        ->call('next')
        ->assertHasErrors('reason')
        ->assertSet('step', 2)
        ->set('reason', 'مستلزمات لم تُستخدم')
        ->call('next')
        ->assertHasNoErrors()
        ->assertSet('step', 3);
});

it('submits the petty-cash refund and reaches the done state', function () {
    Http::fake([
        '*/api/provider/returns' => Http::response(['success' => true, 'data' => [
            'uuid' => 'RT9', 'type' => 'petty_cash_refund', 'amount' => 45000, 'status' => 'pending',
        ]], 201),
    ]);

    Livewire::test(PettyCashRefund::class)
        ->set('pettyCashUuid', 'PC1')
        ->set('reason', 'دفع زائد للمورد')
        ->set('notes', 'تم إرجاع المبلغ للخزنة')
        ->set('step', 3)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('done', true)
        ->assertDispatched('notify');

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && str_contains($request->url(), '/api/provider/returns')
            && $request['type'] === 'petty_cash_refund'
            && $request['petty_cash_uuid'] === 'PC1'
            && $request['amount'] === 45000;
    });
});

it('still completes when the API is unreachable (fallback)', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(PettyCashRefund::class)
        ->set('pettyCashUuid', 'PC2')
        ->set('reason', 'سبب آخر')
        ->set('step', 3)
        ->call('submit')
        ->assertSet('done', true)
        ->assertDispatched('notify');
});

it('resets the wizard when starting another refund', function () {
    Http::fake(['*' => Http::response(['success' => true, 'data' => []], 201)]);

    Livewire::test(PettyCashRefund::class)
        ->set('pettyCashUuid', 'PC1')
        ->set('reason', 'صرف مزدوج بالخطأ')
        ->set('step', 3)
        ->call('submit')
        ->assertSet('done', true)
        ->call('startAnother')
        ->assertSet('done', false)
        ->assertSet('step', 1)
        ->assertSet('pettyCashUuid', null)
        ->assertSet('reason', '');
});
