<?php

declare(strict_types=1);

use App\Livewire\Bookings\Waitlist;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeWaitlist(): void
{
    Http::fake([
        '*/api/provider/waitlist/*/notify' => Http::response(['success' => true], 200),
        '*/api/provider/waitlist/*' => Http::response(['success' => true], 200),
        '*/api/provider/waitlist*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'WL1', 'customer' => ['name' => 'Fatima Rashad', 'phone' => '01011112222'], 'service' => ['name' => 'Hair Color'], 'preferred_date' => '2026-07-05', 'preferred_time' => '11:00:00', 'status' => 'waiting', 'position' => 1],
            ['uuid' => 'WL2', 'customer' => ['name' => 'Omar Khaled'], 'service' => ['name' => 'Classic Haircut'], 'preferred_date' => '2026-07-05', 'preferred_time' => '13:30:00', 'status' => 'notified', 'position' => 2],
        ]]),
    ]);
}

it('lists waitlist entries from the API', function () {
    fakeWaitlist();

    Livewire::test(Waitlist::class)
        ->assertSee('Fatima Rashad')
        ->assertSee('Hair Color')
        ->assertSee('#1');
});

it('filters the waitlist by status', function () {
    fakeWaitlist();

    Livewire::test(Waitlist::class)
        ->set('statusFilter', 'notified')
        ->assertSee('Omar Khaled')
        ->assertDontSee('Fatima Rashad');
});

it('notifies a waiting entry via PATCH', function () {
    fakeWaitlist();

    Livewire::test(Waitlist::class)
        ->call('notify', 'WL1')
        ->assertSet('overrides.WL1', 'notified');

    Http::assertSent(fn ($req) => $req->method() === 'PATCH'
        && str_contains($req->url(), '/api/provider/waitlist/WL1/notify'));
});

it('removes an entry via DELETE', function () {
    fakeWaitlist();

    Livewire::test(Waitlist::class)
        ->call('confirmRemove', 'WL1')
        ->assertSet('showDelete', true)
        ->call('remove')
        ->assertSet('showDelete', false);

    Http::assertSent(fn ($req) => $req->method() === 'DELETE'
        && str_contains($req->url(), '/api/provider/waitlist/WL1'));
});

it('falls back to sample data when the API is unreachable', function () {
    Http::fake([
        '*/api/provider/waitlist*' => Http::response('', 500),
    ]);

    Livewire::test(Waitlist::class)
        ->assertSee('sample data');
});
