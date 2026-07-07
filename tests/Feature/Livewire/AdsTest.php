<?php

declare(strict_types=1);

use App\Livewire\Marketing\Ads;
use Livewire\Livewire;

it('lists seeded ad placements', function () {
    Livewire::test(Ads::class)
        ->assertSee('بانر الصفحة الرئيسية')
        ->assertSee('إعلان مميّز');
});

it('creates an ad placement', function () {
    $component = Livewire::test(Ads::class)
        ->call('openCreate')
        ->set('form_name', 'Sidebar Promo')
        ->set('form_placement', 'featured')
        ->set('form_price', '1500')
        ->set('form_status', 'active')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    expect(collect($component->get('items'))->pluck('name'))->toContain('Sidebar Promo');
    expect($component->get('items'))->toHaveCount(4);
});

it('deletes an ad placement', function () {
    $component = Livewire::test(Ads::class);
    expect($component->get('items'))->toHaveCount(3);

    $component->call('confirmDelete', 'ad-1')
        ->call('deleteAd');

    expect($component->get('items'))->toHaveCount(2);
    expect(collect($component->get('items'))->pluck('name'))->not->toContain('بانر الصفحة الرئيسية');
});
