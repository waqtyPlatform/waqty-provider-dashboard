<?php

declare(strict_types=1);

use App\Livewire\Employees\Deductions;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeDeductions(): void
{
    Http::fake([
        '*/api/provider/deductions/*' => Http::response(['success' => true], 200),
        '*/api/provider/deductions' => Http::response(['success' => true, 'data' => ['uuid' => 'NEW']], 200),
        '*/api/provider/deductions*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'DD1', 'employee' => 'Sara Ahmed', 'type' => 'absence', 'amount' => 15000, 'reason' => 'Unexcused absence', 'date' => '2026-07-05'],
            ['uuid' => 'DD2', 'employee' => 'Khaled Hassan', 'type' => 'late', 'amount' => 5000, 'reason' => 'Repeated lateness', 'date' => '2026-07-04'],
        ]]),
    ]);
}

it('lists deductions from the API', function () {
    fakeDeductions();

    Livewire::test(Deductions::class)
        ->assertSee('Sara Ahmed')
        ->assertSee('Khaled Hassan')
        ->assertSee('Unexcused absence');
});

it('falls back to Arabic sample deductions when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Deductions::class)
        ->assertSee('sample data')
        ->assertSee('سارة أحمد')
        ->assertSee('غياب بدون إذن مسبق');
});

it('filters deductions by type', function () {
    fakeDeductions();

    Livewire::test(Deductions::class)
        ->set('typeFilter', 'late')
        ->assertSee('Khaled Hassan')
        ->assertDontSee('Sara Ahmed');
});

it('validates the create form', function () {
    fakeDeductions();

    Livewire::test(Deductions::class)
        ->call('openCreate')
        ->set('form_employee', '')
        ->set('form_amount', '')
        ->set('form_reason', '')
        ->call('save')
        ->assertHasErrors(['form_employee', 'form_amount', 'form_reason'])
        ->assertSet('showForm', true);
});

it('records a new deduction and notifies', function () {
    fakeDeductions();

    Livewire::test(Deductions::class)
        ->call('openCreate')
        ->set('form_employee', 'سارة أحمد')
        ->set('form_type', 'absence')
        ->set('form_amount', '250')
        ->set('form_reason', 'غياب بدون إذن')
        ->set('form_date', '2026-07-06')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/api/provider/deductions')
        && $req['amount'] === 25000
        && $req['type'] === 'absence');
});
