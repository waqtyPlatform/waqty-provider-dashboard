<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Appearance preferences. Theme is applied instantly on the client (writing the
 * same `waqty_theme` cookie the top-bar toggle uses); brand colour and the
 * layout toggles persist to the session.
 */
#[Layout('components.layouts.app')]
#[Title('Appearance — Waqty')]
class Appearance extends Component
{
    public string $brandColor = '#00b166';

    public bool $compactSidebar = false;

    public bool $showAnimations = true;

    public function mount(): void
    {
        foreach (session('waqty.settings.appearance', []) as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    public function save(): void
    {
        $this->validate([
            'brandColor' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        session(['waqty.settings.appearance' => [
            'brandColor' => $this->brandColor,
            'compactSidebar' => $this->compactSidebar,
            'showAnimations' => $this->showAnimations,
        ]]);

        $this->dispatch('notify', type: 'success', message: __('settings.appearance.saved'));
    }

    public function render()
    {
        return view('livewire.settings.appearance');
    }
}
