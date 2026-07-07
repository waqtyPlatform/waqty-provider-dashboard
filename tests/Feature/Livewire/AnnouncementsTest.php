<?php

declare(strict_types=1);

use App\Livewire\Marketing\Announcements;
use Livewire\Livewire;

it('renders and lists a seeded announcement', function () {
    Livewire::test(Announcements::class)
        ->assertOk()
        ->assertSee('مواعيد إجازة العيد');
});

it('creates an announcement', function () {
    $component = Livewire::test(Announcements::class)
        ->call('openCreate')
        ->set('form_title', 'Weekend Promo')
        ->set('form_body', 'Enjoy special weekend pricing across all services.')
        ->set('form_priority', 'normal')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    expect(collect($component->get('items'))->pluck('title'))->toContain('Weekend Promo');
});

it('deletes an announcement', function () {
    $component = Livewire::test(Announcements::class);
    $before = count($component->get('items'));

    $component->call('confirmDelete', 'row-1')
        ->call('deleteAnnouncement');

    expect(count($component->get('items')))->toBe($before - 1);
});
