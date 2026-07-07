<?php

declare(strict_types=1);

use App\Livewire\Employees\Transfers;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeTransfers(): void
{
    Http::fake([
        '*/api/provider/employee-transfers/*' => Http::response(['success' => true], 200),
        '*/api/provider/employee-transfers' => Http::response(['success' => true, 'data' => ['uuid' => 'NEW']], 200),
        '*/api/provider/employee-transfers*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'TR1', 'employee' => 'Sara Ahmed', 'from_branch' => 'Downtown', 'to_branch' => 'Arabella', 'type' => 'permanent', 'until_date' => null, 'status' => 'pending'],
            ['uuid' => 'TR2', 'employee' => 'Khaled Hassan', 'from_branch' => 'Arabella', 'to_branch' => 'Maadi', 'type' => 'temporary', 'until_date' => '2026-08-15', 'status' => 'approved'],
        ]]),
    ]);
}

it('lists transfers from the API', function () {
    fakeTransfers();

    Livewire::test(Transfers::class)
        ->assertOk()
        ->assertSee('Sara Ahmed')
        ->assertSee('Khaled Hassan')
        ->assertSee('Downtown')
        ->assertSee('Maadi');
});

it('falls back to Arabic sample transfers when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Transfers::class)
        ->assertSee('sample data')
        ->assertSee('سارة أحمد')
        ->assertSee('مول العرب')
        ->assertSee('طارق سامي');
});

it('filters transfers by status', function () {
    fakeTransfers();

    Livewire::test(Transfers::class)
        ->set('statusFilter', 'approved')
        ->assertSee('Khaled Hassan')
        ->assertDontSee('Sara Ahmed');
});

it('validates the create form', function () {
    fakeTransfers();

    Livewire::test(Transfers::class)
        ->call('openCreate')
        ->call('createTransfer')
        ->assertHasErrors(['form_employee', 'form_from_branch', 'form_to_branch'])
        ->assertSet('showForm', true);
});

it('rejects a transfer to the same branch', function () {
    fakeTransfers();

    Livewire::test(Transfers::class)
        ->call('openCreate')
        ->set('form_employee', 'سارة أحمد')
        ->set('form_from_branch', 'وسط البلد')
        ->set('form_to_branch', 'وسط البلد')
        ->call('createTransfer')
        ->assertHasErrors(['form_to_branch']);
});

it('requires a future end date for a temporary transfer', function () {
    fakeTransfers();

    Livewire::test(Transfers::class)
        ->call('openCreate')
        ->set('form_employee', 'سارة أحمد')
        ->set('form_from_branch', 'وسط البلد')
        ->set('form_to_branch', 'مول العرب')
        ->set('form_type', 'temporary')
        ->set('form_until_date', '')
        ->call('createTransfer')
        ->assertHasErrors(['form_until_date']);
});

it('creates a transfer and notifies', function () {
    fakeTransfers();

    $future = Carbon::now()->addMonths(2)->format('Y-m-d');

    Livewire::test(Transfers::class)
        ->call('openCreate')
        ->set('form_employee', 'سارة أحمد')
        ->set('form_from_branch', 'وسط البلد')
        ->set('form_to_branch', 'مول العرب')
        ->set('form_type', 'temporary')
        ->set('form_until_date', $future)
        ->call('createTransfer')
        ->assertSet('showForm', false)
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/api/provider/employee-transfers')
        && $req['employee'] === 'سارة أحمد'
        && $req['type'] === 'temporary');
});

it('approves a transfer and notifies', function () {
    fakeTransfers();

    Livewire::test(Transfers::class)
        ->call('approveTransfer', 'TR1')
        ->assertDispatched('notify');

    Http::assertSent(fn ($req) => $req->method() === 'PATCH'
        && str_contains($req->url(), '/api/provider/employee-transfers/TR1/approve'));
});
