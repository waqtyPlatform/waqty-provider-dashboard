<?php

declare(strict_types=1);

use App\Livewire\Marketing\Campaigns;
use Livewire\Livewire;

it('renders and lists a seeded campaign', function () {
    Livewire::test(Campaigns::class)
        ->assertOk()
        ->assertSee('حملة تخفيضات الصيف');
});

it('creates a campaign', function () {
    $component = Livewire::test(Campaigns::class)
        ->call('openCreate')
        ->set('form_name', 'Flash Friday')
        ->set('form_channel', 'push')
        ->set('form_status', 'active')
        ->set('form_audience', 'vip')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    expect(collect($component->get('items'))->pluck('name'))->toContain('Flash Friday');
});

it('deletes a campaign', function () {
    $component = Livewire::test(Campaigns::class);
    $before = count($component->get('items'));

    $component->call('confirmDelete', 'camp-1')
        ->call('deleteCampaign');

    expect(count($component->get('items')))->toBe($before - 1);
});
