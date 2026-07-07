<?php

declare(strict_types=1);

use App\Livewire\Employees\Payroll;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakePayroll(): void
{
    Http::fake([
        '*/api/provider/payroll/*' => Http::response(['success' => true], 200),
        '*/api/provider/payroll*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'PA1', 'employee' => 'Sara Ahmed', 'period' => '2026-07', 'base_salary' => 800000, 'commissions' => 150000, 'deductions' => 20000, 'net' => 930000, 'status' => 'paid'],
            ['uuid' => 'PA2', 'employee' => 'Mona Adel', 'period' => '2026-07', 'base_salary' => 650000, 'commissions' => 90000, 'deductions' => 5000, 'net' => 735000, 'status' => 'approved'],
            ['uuid' => 'PA3', 'employee' => 'Khaled Hassan', 'period' => '2026-07', 'base_salary' => 700000, 'commissions' => 60000, 'deductions' => 15000, 'net' => 745000, 'status' => 'draft'],
        ]]),
    ]);
}

it('lists payroll runs from the API', function () {
    fakePayroll();

    Livewire::test(Payroll::class)
        ->assertSee('Sara Ahmed')
        ->assertSee('Mona Adel')
        ->assertSee('Khaled Hassan');
});

it('falls back to Arabic sample payroll when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(Payroll::class)
        ->assertSee('sample data')
        ->assertSee('سارة أحمد')
        ->assertSee('طارق سامي');
});

it('filters payroll by status', function () {
    fakePayroll();

    Livewire::test(Payroll::class)
        ->set('statusFilter', 'paid')
        ->assertSee('Sara Ahmed')
        ->assertDontSee('Khaled Hassan');
});

it('validates the generate payroll form', function () {
    fakePayroll();

    Livewire::test(Payroll::class)
        ->call('openGenerate')
        ->set('form_period', '')
        ->call('generatePayroll')
        ->assertHasErrors(['form_period'])
        ->assertSet('showGenerate', true);
});

it('records a payment and notifies', function () {
    fakePayroll();

    Livewire::test(Payroll::class)
        ->call('openPay', 'PA2')
        ->assertSet('showPay', true)
        ->set('pay_method', 'bank')
        ->set('pay_notes', 'راتب شهر يوليو')
        ->call('payPayroll')
        ->assertSet('showPay', false)
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    Http::assertSent(fn ($req) => $req->method() === 'PATCH'
        && str_contains($req->url(), '/api/provider/payroll/PA2/pay')
        && $req['amount'] === 735000
        && $req['method'] === 'bank');
});
