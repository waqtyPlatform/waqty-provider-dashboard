<?php

declare(strict_types=1);

use App\Livewire\Settings\Integrations;
use Livewire\Livewire;

it('lists integrations with their names and descriptions', function () {
    Livewire::test(Integrations::class)
        ->assertSee('WhatsApp Business')
        ->assertSee('Mailchimp')
        ->assertSee('نمِّ جمهورك وأطلق حملات تسويق عبر البريد الإلكتروني.');
});

it('toggles a disconnected integration to connected locally', function () {
    $items = Livewire::test(Integrations::class)
        ->call('toggle', 3)
        ->get('items');

    $mailchimp = collect($items)->firstWhere('id', 3);

    expect($mailchimp['connected'])->toBeTrue();
});

it('toggles a connected integration off locally', function () {
    $items = Livewire::test(Integrations::class)
        ->call('toggle', 1)
        ->get('items');

    $whatsapp = collect($items)->firstWhere('id', 1);

    expect($whatsapp['connected'])->toBeFalse();
});
