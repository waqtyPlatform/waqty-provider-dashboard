<?php

declare(strict_types=1);

use App\Livewire\Settings\PricingGroups;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('lists pricing groups', function () {
    Http::fake(['*/api/provider/pricing-groups' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'PG1', 'name' => 'Senior Stylists', 'active' => true, 'employees_count' => 12],
        ['uuid' => 'PG2', 'name' => 'Junior Staff', 'active' => true, 'employees_count' => 8],
    ]])]);

    Livewire::test(PricingGroups::class)
        ->assertSee('Senior Stylists')
        ->assertSee('Junior Staff');
});

it('creates a pricing group', function () {
    Http::fake(['*/api/provider/pricing-groups' => Http::response(['success' => true, 'data' => [
        ['uuid' => 'PG1', 'name' => 'Senior Stylists', 'active' => true, 'employees_count' => 12],
    ]])]);

    Livewire::test(PricingGroups::class)
        ->call('openCreate')
        ->set('form_name', 'VIP Team')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    Http::assertSent(fn ($req) => $req->method() === 'POST'
        && str_contains($req->url(), '/pricing-groups')
        && $req['name'] === 'VIP Team');
});

it('falls back to sample pricing groups when the API is down', function () {
    Http::fake(['*' => Http::response(['message' => 'error'], 500)]);

    Livewire::test(PricingGroups::class)
        ->assertSee('sample data')
        ->assertSee('كبار المصففين');
});
