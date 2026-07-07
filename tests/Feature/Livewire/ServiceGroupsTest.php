<?php

declare(strict_types=1);

use App\Livewire\Marketing\ServiceGroups;
use Livewire\Livewire;

it('renders and lists a seeded service group', function () {
    Livewire::test(ServiceGroups::class)
        ->assertOk()
        ->assertSee('باقة العرائس');
});

it('creates a service group', function () {
    $component = Livewire::test(ServiceGroups::class)
        ->call('openCreate')
        ->set('form_name', 'Holiday Bundle')
        ->set('form_color', '#f59e0b')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    expect(collect($component->get('items'))->pluck('name'))->toContain('Holiday Bundle');
});

it('deletes a service group', function () {
    $component = Livewire::test(ServiceGroups::class);
    $before = count($component->get('items'));

    $component->call('confirmDelete', 'grp-1')
        ->call('deleteGroup')
        ->assertSet('showDelete', false);

    expect(count($component->get('items')))->toBe($before - 1);
});
