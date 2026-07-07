<?php

declare(strict_types=1);

use App\Livewire\Transactions\Shifts;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('falls back to sample shift totals when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Shifts::class)
        ->assertSee('sample data')
        ->assertSee('سارة أحمد')
        ->assertSee('وردية صباحية');
});

it('closes an open shift through the finance API', function () {
    Http::fake([
        '*/api/provider/transactions/shift-totals/*/close' => Http::response(['success' => true], 200),
        '*/api/provider/transactions/shift-totals*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'S1', 'label' => 'وردية صباحية', 'cashier' => 'سارة أحمد', 'opened_at' => '2026-07-05 08:00:00', 'closed_at' => null, 'expected_total' => 32000, 'actual_total' => null, 'variance' => 0, 'status' => 'open'],
        ]]),
    ]);

    Livewire::test(Shifts::class)
        ->call('openClose', 'S1')
        ->assertSet('showClose', true)
        ->call('close')
        ->assertSet('closedOverrides.S1', true)
        ->assertSet('showClose', false)
        ->assertDispatched('notify');

    Http::assertSent(fn ($req) => $req->method() === 'PATCH'
        && str_contains($req->url(), '/api/provider/transactions/shift-totals/S1/close'));
});

it('optimistically closes a shift and notifies under fallback', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Shifts::class)
        ->call('openClose', 'S1')
        ->call('close')
        ->assertSet('closedOverrides.S1', true)
        ->assertDispatched('notify');
});
