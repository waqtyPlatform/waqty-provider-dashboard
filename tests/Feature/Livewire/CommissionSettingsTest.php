<?php

declare(strict_types=1);

use App\Livewire\Employees\CommissionSettings;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function fakeCommissionRules(): void
{
    Http::fake([
        '*/api/provider/commission-rules/*' => Http::response(['success' => true], 200),
        '*/api/provider/commission-rules' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'A1', 'type' => 'service', 'name' => 'Haircut & styling', 'rate' => 10, 'active' => true],
            ['uuid' => 'A2', 'type' => 'tier', 'name' => 'Above EGP 30,000', 'threshold' => 3000000, 'multiplier' => 1.25, 'active' => true],
            ['uuid' => 'A3', 'type' => 'segment', 'name' => 'VIP clients', 'rate' => 5, 'active' => true],
        ]]),
    ]);
}

it('renders commission rules from the API across tabs', function () {
    fakeCommissionRules();

    Livewire::test(CommissionSettings::class)
        ->assertSee('Haircut & styling')
        ->set('tab', 'tier')
        ->assertSee('Above EGP 30,000')
        ->set('tab', 'segment')
        ->assertSee('VIP clients');
});

it('falls back to Arabic sample rules when the API is unavailable', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(CommissionSettings::class)
        ->assertSee('sample data')
        ->assertSee('قص وتصفيف الشعر')
        ->assertSee('صبغة الشعر');
});

it('validates the rule form', function () {
    fakeCommissionRules();

    Livewire::test(CommissionSettings::class)
        ->call('openCreate', 'service')
        ->set('form_label', '')
        ->set('form_rate', '')
        ->call('save')
        ->assertHasErrors(['form_label' => 'required', 'form_rate' => 'required'])
        ->assertSet('showForm', true);
});

it('creates a service rate, notifies, and sends the rate', function () {
    fakeCommissionRules();

    Livewire::test(CommissionSettings::class)
        ->call('openCreate', 'service')
        ->set('form_label', 'Manicure')
        ->set('form_rate', '12')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/api/provider/commission-rules')
        && $req['type'] === 'service'
        && (float) $req['rate'] === 12.0);
});

it('creates a target tier and sends the threshold as minor units', function () {
    fakeCommissionRules();

    Livewire::test(CommissionSettings::class)
        ->call('openCreate', 'tier')
        ->set('form_label', 'Above EGP 40,000')
        ->set('form_threshold', '40000')
        ->set('form_multiplier', '1.5')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && $req['type'] === 'tier'
        && $req['threshold'] === 4000000
        && (float) $req['multiplier'] === 1.5);
});

it('validates and saves the general settings via save all', function () {
    fakeCommissionRules();

    Livewire::test(CommissionSettings::class)
        ->set('baseRate', '150')
        ->call('saveAll')
        ->assertHasErrors('baseRate')
        ->set('baseRate', '12')
        ->call('saveAll')
        ->assertHasNoErrors()
        ->assertDispatched('notify');
});
