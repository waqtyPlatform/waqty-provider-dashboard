<?php

declare(strict_types=1);

use App\Livewire\Settings\DataManagement;
use Livewire\Livewire;

it('renders the data & backup page with its seeded content', function () {
    Livewire::test(DataManagement::class)
        ->assertOk()
        ->assertSee('Daily Auto-Backup')
        ->assertSee('Export All Data');
});

it('persists the auto-backup toggle to the session on save', function () {
    Livewire::test(DataManagement::class)
        ->assertSet('autoBackup', true)
        ->set('autoBackup', false)
        ->call('save')
        ->assertHasNoErrors();

    expect(session('waqty.settings.data.autoBackup'))->toBeFalse();
});

it('emits a success toast when export is triggered', function () {
    Livewire::test(DataManagement::class)
        ->call('export')
        ->assertDispatched('notify', type: 'success');
});
