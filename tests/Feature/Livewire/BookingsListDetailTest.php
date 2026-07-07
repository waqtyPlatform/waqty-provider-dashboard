<?php

declare(strict_types=1);

use App\Livewire\Bookings\BookingDetail;
use App\Livewire\Bookings\BookingList;
use App\Livewire\Bookings\NewBooking;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeBookingList(): void
{
    Http::fake([
        '*/api/provider/bookings/*/cancel' => Http::response(['success' => true], 200),
        '*/api/provider/bookings*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'BK1', 'user' => ['name' => 'Fatima Rashad', 'phone' => '01011112222'], 'service' => ['name' => 'Hair Color'], 'employee' => ['name' => 'Sara Ahmed'], 'booking_date' => '2026-07-03', 'start_time' => '09:00:00', 'status' => 'confirmed', 'price' => 45000],
            ['uuid' => 'BK2', 'user' => ['name' => 'Omar Khaled'], 'service' => ['name' => 'Classic Haircut'], 'booking_date' => '2026-07-03', 'start_time' => '09:30:00', 'status' => 'completed', 'price' => 15000],
        ]]),
    ]);
}

it('lists bookings from the API', function () {
    fakeBookingList();

    Livewire::test(BookingList::class)
        ->assertSee('Fatima Rashad')
        ->assertSee('Hair Color')
        ->assertSee('450 EGP');
});

it('searches bookings by client name', function () {
    fakeBookingList();

    Livewire::test(BookingList::class)
        ->set('search', 'omar')
        ->assertSee('Omar Khaled')
        ->assertDontSee('Fatima Rashad');
});

it('cancels a booking via the API', function () {
    fakeBookingList();

    Livewire::test(BookingList::class)
        ->call('confirmCancel', 'BK1')
        ->assertSet('showCancel', true)
        ->set('cancelReason', 'Client request')
        ->call('cancelBooking')
        ->assertSet('showCancel', false);

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/api/provider/bookings/BK1/cancel')
        && $req['cancellation_reason'] === 'Client request');
});

it('shows booking detail with status transitions', function () {
    Http::fake([
        '*/api/provider/bookings/BK1/activities' => Http::response(['success' => true, 'data' => []]),
        '*/api/provider/bookings/BK1/status' => Http::response(['success' => true], 200),
        '*/api/provider/bookings/BK1' => Http::response(['success' => true, 'data' => [
            'uuid' => 'BK1', 'user' => ['name' => 'Fatima'], 'service' => ['name' => 'Hair Color'], 'booking_date' => '2026-07-03', 'start_time' => '09:00:00', 'status' => 'pending', 'price' => 45000,
        ]]),
    ]);

    Livewire::test(BookingDetail::class, ['uuid' => 'BK1'])
        ->assertSee('Hair Color')
        ->call('changeStatus', 'confirmed')
        ->assertSet('statusOverride', 'confirmed');

    Http::assertSent(fn ($req) => $req->method() === 'PATCH'
        && str_contains($req->url(), '/api/provider/bookings/BK1/status')
        && $req['status'] === 'confirmed');
});

it('rejects an illegal status transition', function () {
    Http::fake([
        '*/api/provider/bookings/BK1/activities' => Http::response(['success' => true, 'data' => []]),
        '*/api/provider/bookings/BK1' => Http::response(['success' => true, 'data' => [
            'uuid' => 'BK1', 'user' => ['name' => 'Fatima'], 'service' => ['name' => 'Hair Color'], 'status' => 'pending',
        ]]),
    ]);

    // pending -> completed is illegal; nothing should change.
    Livewire::test(BookingDetail::class, ['uuid' => 'BK1'])
        ->call('changeStatus', 'completed')
        ->assertSet('statusOverride', null);
});

it('validates the new booking form', function () {
    Http::fake([
        '*/api/provider/service-prices*' => Http::response(['success' => true, 'data' => []]),
        '*/api/provider/services*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'S1', 'name' => 'Haircut', 'estimated_duration_minutes' => 30, 'active' => true],
        ]]),
        '*/api/provider/employees*' => Http::response(['success' => true, 'data' => []]),
    ]);

    Livewire::test(NewBooking::class)
        ->set('form_service', '')
        ->set('form_client_name', '')
        ->call('save')
        ->assertHasErrors(['form_service' => 'required', 'form_client_name' => 'required']);
});

it('creates a new booking via the API', function () {
    Http::fake([
        '*/api/provider/service-prices*' => Http::response(['success' => true, 'data' => []]),
        '*/api/provider/services*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'S1', 'name' => 'Haircut', 'estimated_duration_minutes' => 30, 'active' => true],
        ]]),
        '*/api/provider/employees*' => Http::response(['success' => true, 'data' => []]),
        '*/api/provider/bookings' => Http::response(['success' => true, 'data' => ['uuid' => 'BKNEW']], 200),
    ]);

    Livewire::test(NewBooking::class)
        ->set('form_service', 'S1')
        ->set('form_date', '2026-07-10')
        ->set('form_time', '10:00')
        ->set('form_client_name', 'Layla')
        ->call('save');

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/api/provider/bookings')
        && $req['service_uuid'] === 'S1'
        && $req['user_name'] === 'Layla');
});
