<?php

declare(strict_types=1);

use App\Livewire\Marketing\Packages;
use Livewire\Livewire;

it('lists seeded Arabic packages with the sample-data banner', function () {
    Livewire::test(Packages::class)
        ->assertSee('باقة العرائس')
        ->assertSee('باقة العناية بالبشرة')
        ->assertSee(__('common.sampleData'));
});

it('creates a package, validates and notifies', function () {
    $component = Livewire::test(Packages::class)
        ->call('openCreate')
        ->set('form_name', 'باقة الشتاء')
        ->set('form_price', '1500')
        ->set('form_original', '2000')
        ->set('form_sessions', '4')
        ->set('form_services', ['svc-skin', 'svc-mani'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showForm', false)
        ->assertDispatched('notify');

    expect(collect($component->instance()->packages)->pluck('name'))->toContain('باقة الشتاء');
});

it('validates the package form', function () {
    Livewire::test(Packages::class)
        ->call('openCreate')
        ->set('form_name', '')
        ->set('form_price', '0')
        ->set('form_sessions', '')
        ->set('form_services', [])
        ->call('save')
        ->assertHasErrors(['form_name', 'form_price', 'form_sessions', 'form_services']);
});

it('toggles a package active state', function () {
    $component = Livewire::test(Packages::class)->call('toggleActive', 1);

    $pkg = collect($component->instance()->packages)->firstWhere('id', 1);
    expect($pkg['active'])->toBeFalse();
});

it('deletes a package', function () {
    $component = Livewire::test(Packages::class)
        ->call('confirmDelete', 2)
        ->call('deletePackage')
        ->assertDispatched('notify');

    expect(collect($component->instance()->packages)->pluck('id'))->not->toContain(2);
});
