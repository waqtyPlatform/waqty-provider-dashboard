<?php

declare(strict_types=1);

use App\Livewire\Marketing\Messages;
use Livewire\Livewire;

it('renders and lists a seeded message template', function () {
    Livewire::test(Messages::class)
        ->assertOk()
        ->assertSee('تأكيد الحجز')
        ->assertSee('طلب تقييم');
});

it('creates a message template', function () {
    $component = Livewire::test(Messages::class)
        ->call('openCreate')
        ->set('form_name', 'Appointment Reminder')
        ->set('form_channel', 'push')
        ->set('form_body', 'Reminder: your appointment is tomorrow.')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    expect(collect($component->get('items'))->pluck('name'))->toContain('Appointment Reminder');
    expect($component->get('items'))->toHaveCount(4);
});

it('deletes a message template', function () {
    $component = Livewire::test(Messages::class);

    expect($component->get('items'))->toHaveCount(3);

    $component->call('confirmDelete', 'msg-2')
        ->call('deleteMessage')
        ->assertHasNoErrors();

    expect($component->get('items'))->toHaveCount(2);
    expect(collect($component->get('items'))->pluck('name'))->not->toContain('طلب تقييم');
});
