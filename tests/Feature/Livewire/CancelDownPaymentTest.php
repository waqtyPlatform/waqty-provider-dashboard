<?php

declare(strict_types=1);

use App\Livewire\Returns\CancelDownPayment;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('renders step 1 with the Arabic sample bookings', function () {
    Livewire::test(CancelDownPayment::class)
        ->assertOk()
        ->assertSet('step', 1)
        ->assertSee('فاطمة رشاد')
        ->assertSee('صبغة شعر')
        ->assertSee('منى عبد الله');
});

it('requires a booking before leaving step 1', function () {
    Livewire::test(CancelDownPayment::class)
        ->call('next')
        ->assertHasErrors('bookingUuid')
        ->assertSet('step', 1);
});

it('advances through the wizard steps', function () {
    Livewire::test(CancelDownPayment::class)
        ->call('selectBooking', 'BK-3182')
        ->assertSet('bookingUuid', 'BK-3182')
        ->call('next')
        ->assertSet('step', 2)
        ->set('reason', 'client_request')
        ->call('next')
        ->assertSet('step', 3)
        ->call('back')
        ->assertSet('step', 2);
});

it('submits the cancellation, posts the return, and reaches the done state', function () {
    Http::fake(['*/api/provider/returns' => Http::response(['success' => true, 'data' => [
        'uuid' => 'RT-NEW', 'type' => 'cancel_down_payment', 'amount' => 25000, 'status' => 'pending',
    ]])]);

    Livewire::test(CancelDownPayment::class)
        ->call('selectBooking', 'BK-3182')
        ->call('next')
        ->set('reason', 'client_request')
        ->set('notes', 'العميلة طلبت الإلغاء')
        ->call('next')
        ->assertSet('step', 3)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('done', true)
        ->assertDispatched('notify', type: 'success');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/api/provider/returns')
        && $request['type'] === 'cancel_down_payment'
        && $request['booking_uuid'] === 'BK-3182'
        && $request['amount'] === 25000);
});

it('still lands on the done state when the API is unreachable (fallback)', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(CancelDownPayment::class)
        ->call('selectBooking', 'BK-3160')
        ->call('next')
        ->set('reason', 'other')
        ->call('next')
        ->call('submit')
        ->assertSet('done', true)
        ->assertDispatched('notify', type: 'success');
});
