<?php

declare(strict_types=1);

use App\Livewire\Settings\Devices;
use Livewire\Livewire;

it('lists the logged-in devices', function () {
    Livewire::test(Devices::class)
        ->assertSee('كروم على ويندوز')
        ->assertSee('القاهرة')
        ->assertSee('الإسكندرية');
});

it('revokes a device from the local list', function () {
    $c = Livewire::test(Devices::class);
    expect($c->get('items'))->toHaveCount(3);

    $c->call('revoke', 1);

    $ids = array_column($c->get('items'), 'id');
    expect($c->get('items'))->toHaveCount(2)
        ->and($ids)->not->toContain(1);
});

it('leaves other devices intact when one is revoked', function () {
    $c = Livewire::test(Devices::class)->call('revoke', 2);

    $ids = array_column($c->get('items'), 'id');
    expect($ids)->toContain(1)
        ->and($ids)->toContain(3)
        ->and($ids)->not->toContain(2);
});
