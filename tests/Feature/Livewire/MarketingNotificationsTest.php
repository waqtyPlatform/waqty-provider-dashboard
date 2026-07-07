<?php

declare(strict_types=1);

use App\Livewire\Marketing\MarketingNotifications;
use Livewire\Livewire;

it('renders and lists a seeded notification', function () {
    Livewire::test(MarketingNotifications::class)
        ->assertOk()
        ->assertSee('تخفيضات سريعة اليوم!')
        ->assertSee('عرض حصري لكبار العملاء');
});

it('composes a new notification', function () {
    $component = Livewire::test(MarketingNotifications::class)
        ->call('openCreate')
        ->set('form_title', 'Weekend Bonus')
        ->set('form_body', 'Enjoy an extra treat on us this weekend.')
        ->set('form_audience', 'vip')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    $items = $component->get('items');
    expect($items)->toHaveCount(4);
    expect(collect($items)->pluck('title'))->toContain('Weekend Bonus');
});

it('deletes a notification', function () {
    $component = Livewire::test(MarketingNotifications::class)
        ->call('confirmDelete', 'ntf-1')
        ->call('deleteNotification')
        ->assertSet('showDelete', false);

    expect($component->get('items'))->toHaveCount(2);
    expect(collect($component->get('items'))->pluck('id'))->not->toContain('ntf-1');
});
