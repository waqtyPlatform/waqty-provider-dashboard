<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Services\Waqty\SettingsService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Tipping configuration — toggle, quick-tip percentages, custom amounts, and
 * distribution model. GET/PUT `/api/provider/settings/tipping`; falls back to
 * sensible defaults when the API is unavailable.
 */
#[Layout('components.layouts.app')]
#[Title('Tipping — Waqty')]
class Tipping extends Component
{
    use HandlesWaqtyErrors;

    public bool $enabled = true;

    /** @var list<int> */
    public array $percentages = [10, 15, 20];

    public string $newPercentage = '';

    public bool $allowCustom = true;

    public string $distribution = 'individual';

    public bool $fallbackUsed = false;

    public function mount(): void
    {
        try {
            $s = app(SettingsService::class)->tippingSettings();
            $this->enabled = (bool) ($s['enabled'] ?? true);
            $this->allowCustom = (bool) ($s['allow_custom'] ?? true);
            $this->distribution = in_array($s['distribution'] ?? '', ['individual', 'pool', 'split'], true)
                ? $s['distribution']
                : 'individual';
            $pcts = array_values(array_filter(array_map('intval', (array) ($s['percentages'] ?? []))));
            if ($pcts !== []) {
                sort($pcts);
                $this->percentages = array_values(array_unique($pcts));
            }
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
        }
    }

    public function addPercentage(): void
    {
        $this->validate(['newPercentage' => ['required', 'integer', 'min:1', 'max:100']]);

        $value = (int) $this->newPercentage;
        if (! in_array($value, $this->percentages, true)) {
            $this->percentages[] = $value;
            sort($this->percentages);
        }

        $this->reset('newPercentage');
    }

    public function removePercentage(int $value): void
    {
        $this->percentages = array_values(array_filter($this->percentages, fn ($p) => $p !== $value));
    }

    public function save(): void
    {
        $this->validate(['distribution' => ['required', 'in:individual,pool,split']]);

        if (! $this->fallbackUsed) {
            $this->waqty(fn () => app(SettingsService::class)->updateTippingSettings([
                'enabled' => $this->enabled,
                'percentages' => $this->percentages,
                'allow_custom' => $this->allowCustom,
                'distribution' => $this->distribution,
            ]) ?? true, __('tipping.saveFailed'));
        }

        $this->dispatch('notify', type: 'success', message: __('tipping.saved'));
    }

    public function render()
    {
        return view('livewire.settings.tipping');
    }
}
