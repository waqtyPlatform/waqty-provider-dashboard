<?php

declare(strict_types=1);

use App\Livewire\Employees\Commissions;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeCommissions(): void
{
    Http::fake([
        '*/api/provider/commissions/calculate' => Http::response(['success' => true], 200),
        '*/api/provider/commissions*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'C1', 'tab' => 'by-service', 'employee' => 'Sara Ahmed', 'service' => 'Hair Color', 'base' => 450000, 'rate' => 15, 'commission' => 67500, 'date' => '2026-07-05'],
            ['uuid' => 'C2', 'tab' => 'by-service', 'employee' => 'Khaled Hassan', 'service' => 'Men Cut', 'base' => 200000, 'rate' => 10, 'commission' => 20000, 'date' => '2026-07-04'],
            ['uuid' => 'C7', 'tab' => 'by-segment', 'employee' => 'Sara Ahmed', 'segment' => 'VIP Clients', 'base' => 800000, 'rate' => 20, 'commission' => 160000, 'date' => '2026-07-05'],
        ]]),
    ]);
}

it('renders commissions from the API with formatted totals', function () {
    fakeCommissions();

    Livewire::test(Commissions::class)
        ->assertSee('Sara Ahmed')
        ->assertSee('Khaled Hassan')
        ->assertSee('Hair Color')
        ->assertSee('675'); // 67,500 minor units = 675 EGP commission
});

it('falls back to Arabic sample commissions when the API is unavailable', function () {
    Http::fake(['*/api/provider/commissions*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Commissions::class)
        ->assertSee('sample data')
        ->assertSee('سارة أحمد')
        ->assertSee('صبغة شعر');
});

it('switches to the by-segment breakdown', function () {
    fakeCommissions();

    Livewire::test(Commissions::class)
        ->set('tab', 'by-segment')
        ->assertSee('VIP Clients')
        ->assertDontSee('Men Cut');
});

it('validates the calculate date range', function () {
    fakeCommissions();

    Livewire::test(Commissions::class)
        ->set('dateFrom', '')
        ->set('dateTo', '')
        ->call('calculate')
        ->assertHasErrors(['dateFrom', 'dateTo']);
});

it('recalculates commissions for the selected range and notifies', function () {
    fakeCommissions();

    Livewire::test(Commissions::class)
        ->set('dateFrom', '2026-07-01')
        ->set('dateTo', '2026-07-31')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/api/provider/commissions/calculate')
        && $req['from'] === '2026-07-01'
        && $req['to'] === '2026-07-31');
});
