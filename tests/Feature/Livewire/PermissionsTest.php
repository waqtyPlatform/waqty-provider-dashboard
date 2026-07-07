<?php

declare(strict_types=1);

use App\Livewire\Employees\Permissions;
use Livewire\Livewire;

it('renders the override matrix with Arabic sample employees', function () {
    Livewire::test(Permissions::class)
        ->assertOk()
        ->assertSee('سارة أحمد')
        ->assertSee('منى عادل')
        ->assertSee('خالد حسن')
        ->assertSee('ياسمين فاروق')
        ->assertSee('كبيرة المصففين');
});

it('pre-fills the grid from an admin employee base role (full access)', function () {
    $component = Livewire::test(Permissions::class)->set('selectedUuid', 'E3');

    $perms = $component->get('form_perms');
    expect($perms['settings']['delete'])->toBeTrue()
        ->and($perms['dashboard']['view'])->toBeTrue()
        ->and($component->instance()->overrideCount())->toBe(0);
});

it('pre-fills the grid from a staff employee base role (limited access)', function () {
    $component = Livewire::test(Permissions::class)->set('selectedUuid', 'E2');

    $perms = $component->get('form_perms');
    expect($perms['settings']['view'])->toBeFalse()
        ->and($perms['bookings']['view'])->toBeTrue()
        ->and($perms['bookings']['delete'])->toBeFalse();
});

it('flags a flipped cell as an override', function () {
    $component = Livewire::test(Permissions::class)
        ->set('selectedUuid', 'E2')
        ->set('form_perms.bookings.delete', true);

    expect($component->instance()->overrideCount())->toBe(1)
        ->and($component->instance()->isOverridden('bookings', 'delete'))->toBeTrue();
});

it('saves overrides locally and notifies', function () {
    $component = Livewire::test(Permissions::class)
        ->set('selectedUuid', 'E2')
        ->set('form_perms.bookings.delete', true)
        ->call('save')
        ->assertDispatched('notify');

    expect($component->get('saved')['E2']['bookings']['delete'])->toBeTrue();
});

it('resets all overrides back to the base role', function () {
    $component = Livewire::test(Permissions::class)
        ->set('selectedUuid', 'E2')
        ->set('form_perms.bookings.delete', true)
        ->set('form_perms.settings.view', true);

    expect($component->instance()->overrideCount())->toBe(2);

    $component->call('resetAll');
    expect($component->get('form_perms')['bookings']['delete'])->toBeFalse()
        ->and($component->get('form_perms')['settings']['view'])->toBeFalse()
        ->and($component->instance()->overrideCount())->toBe(0);
});

it('quick-sets a module row and can restore the role default', function () {
    $component = Livewire::test(Permissions::class)
        ->set('selectedUuid', 'E2')
        ->call('setRow', 'settings', 'full');

    expect($component->get('form_perms')['settings']['delete'])->toBeTrue();

    $component->call('setRow', 'settings', 'role');
    expect($component->get('form_perms')['settings']['delete'])->toBeFalse();
});

it('does not notify when saving without a selected employee', function () {
    Livewire::test(Permissions::class)
        ->set('selectedUuid', '')
        ->call('save')
        ->assertNotDispatched('notify');
});
