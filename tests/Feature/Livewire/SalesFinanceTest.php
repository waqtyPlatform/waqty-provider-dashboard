<?php

declare(strict_types=1);

use App\Livewire\Finance\Settlement;
use App\Livewire\Sales\Packages;
use Livewire\Livewire;

it('lists seeded packages with KPIs', function () {
    Livewire::test(Packages::class)
        ->assertSee('إشراقة الصيف')
        ->assertSee('بهجة العروس')
        ->assertSee('Total Packages');
});

it('creates a package via local CRUD', function () {
    Livewire::test(Packages::class)
        ->call('openCreate')
        ->set('form_name', 'Autumn Special')
        ->set('form_price', '180')
        ->set('form_sessions', 4)
        ->set('form_validity', 60)
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors()
        ->assertSee('Autumn Special');
});

it('validates the package form', function () {
    Livewire::test(Packages::class)
        ->call('openCreate')
        ->set('form_name', '')
        ->set('form_price', '')
        ->call('save')
        ->assertHasErrors(['form_name', 'form_price']);
});

it('toggles and deletes a package', function () {
    $component = Livewire::test(Packages::class)
        ->call('toggleActive', 1)
        ->call('confirmDelete', 1)
        ->call('deletePackage');

    $names = collect($component->instance()->packages)->pluck('name');
    expect($names)->not->toContain('إشراقة الصيف')
        ->and($names)->toHaveCount(4);
});

it('renders the settlement summary, payouts and ledger', function () {
    Livewire::test(Settlement::class)
        ->assertSee('Settlement & Payouts')
        ->assertSee('Jun 2026')   // a payout period
        ->assertSee('BK-1042');   // a ledger visit
});
