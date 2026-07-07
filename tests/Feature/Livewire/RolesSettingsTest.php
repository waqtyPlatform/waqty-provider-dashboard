<?php

declare(strict_types=1);

use App\Livewire\Settings\Roles;
use Livewire\Livewire;

it('lists the seeded roles', function () {
    Livewire::test(Roles::class)
        ->assertSee('مدير النظام')
        ->assertSee('مدير')
        ->assertSee('موظف');
});

it('creates a new role with a permission matrix', function () {
    $component = Livewire::test(Roles::class)
        ->call('openCreate')
        ->set('form_name', 'Receptionist')
        ->call('setLevel', 'bookings', 'full')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    $roles = $component->get('roles');
    expect($roles)->toHaveCount(4);
    $new = collect($roles)->firstWhere('name', 'Receptionist');
    expect($new)->not->toBeNull()
        ->and($new['permissions']['bookings']['view'])->toBeTrue()
        ->and($new['permissions']['bookings']['delete'])->toBeTrue()
        ->and($new['permissions']['settings']['view'])->toBeFalse();
});

it('validates the role name is required', function () {
    Livewire::test(Roles::class)
        ->call('openCreate')
        ->set('form_name', '')
        ->call('save')
        ->assertHasErrors(['form_name' => 'required'])
        ->assertSet('showForm', true);
});

it('setLevel view grants only view', function () {
    $component = Livewire::test(Roles::class)
        ->call('openCreate')
        ->call('setLevel', 'reports', 'view');

    $perms = $component->get('form_perms');
    expect($perms['reports']['view'])->toBeTrue()
        ->and($perms['reports']['edit'])->toBeFalse()
        ->and($perms['reports']['delete'])->toBeFalse();
});

it('deletes a non-system role', function () {
    $component = Livewire::test(Roles::class)
        ->call('confirmDelete', 'staff')
        ->call('deleteRole')
        ->assertSet('showDelete', false);

    expect(collect($component->get('roles'))->firstWhere('id', 'staff'))->toBeNull();
});
