<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Data & backup settings. UI-only in the source (localStorage-backed, no API),
 * so the auto-backup preference persists to the session for the demo lifetime.
 * Export/import are simulated with a local success toast.
 */
#[Layout('components.layouts.app')]
#[Title('Data & Backup — Waqty')]
class DataManagement extends Component
{
    public bool $autoBackup = true;

    public function mount(): void
    {
        foreach (session('waqty.settings.data', []) as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    public function export(): void
    {
        $this->dispatch('notify', type: 'success', message: __('settings.data.toastExport'));
    }

    public function import(): void
    {
        $this->dispatch('notify', type: 'success', message: __('settings.data.toastImport'));
    }

    public function save(): void
    {
        session(['waqty.settings.data' => [
            'autoBackup' => $this->autoBackup,
        ]]);

        $this->dispatch('notify', type: 'success', message: __('settings.saved'));
    }

    public function render()
    {
        return view('livewire.settings.data');
    }
}
