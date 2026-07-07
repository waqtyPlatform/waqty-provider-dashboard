<?php

declare(strict_types=1);

use App\Livewire\Settings\ServicePricing;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('loads base prices into the edit map', function () {
    Http::fake([
        '*/api/provider/services*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'S1', 'name' => 'Classic Haircut'],
            ['uuid' => 'S2', 'name' => 'Manicure'],
        ]]),
        '*/api/provider/service-prices*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'PB1', 'service_uuid' => 'S1', 'branch_uuid' => null, 'employee_uuid' => null, 'pricing_group_uuid' => null, 'price' => 15000],
        ]]),
        '*/api/provider/branches' => Http::response(['success' => true, 'data' => []]),
        '*/api/provider/employees*' => Http::response(['success' => true, 'data' => []]),
        '*/api/provider/pricing-groups*' => Http::response(['success' => true, 'data' => []]),
    ]);

    $component = Livewire::test(ServicePricing::class)
        ->assertSet('scope', 'base')
        ->assertSee('Classic Haircut');

    expect($component->get('edits')['S1'])->toBe('150')
        ->and($component->get('edits')['S2'])->toBe('');
});

it('switching scope resets the selected target', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(ServicePricing::class)
        ->assertSet('fallbackUsed', true)
        ->set('scopeId', 'BR1')
        ->set('scope', 'branch')
        ->assertSet('scopeId', null); // updatedScope clears the target
});

it('reloads edits for a chosen branch override', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    // Fallback seeds S1 with a VIP group override (G1) at 200 EGP, base 150.
    $component = Livewire::test(ServicePricing::class)
        ->set('scope', 'group')
        ->set('scopeId', 'G1');

    expect($component->get('edits')['S1'])->toBe('200');
});

it('saves scoped prices via the API', function () {
    Http::fake([
        '*/api/provider/services*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'S1', 'name' => 'Classic Haircut'],
        ]]),
        '*/api/provider/service-prices*' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'PB1', 'service_uuid' => 'S1', 'branch_uuid' => null, 'employee_uuid' => null, 'pricing_group_uuid' => null, 'price' => 15000],
        ]]),
        '*/api/provider/branches' => Http::response(['success' => true, 'data' => [
            ['uuid' => 'BR1', 'name' => 'Downtown'],
        ]]),
        '*/api/provider/employees*' => Http::response(['success' => true, 'data' => []]),
        '*/api/provider/pricing-groups*' => Http::response(['success' => true, 'data' => []]),
    ]);

    Livewire::test(ServicePricing::class)
        ->set('scope', 'branch')
        ->set('scopeId', 'BR1')
        ->set('edits.S1', '175')
        ->call('save');

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/api/provider/service-prices')
        && $req['branch_uuid'] === 'BR1'
        && $req['price'] === 17500);
});
