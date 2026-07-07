<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Subscription & billing. Read-mostly and UI-only in the source (no billing
 * API), so the current plan, usage meters and plan catalogue live in component
 * state. "Upgrade" is a pure local switch of the active plan plus a toast.
 */
#[Layout('components.layouts.app')]
#[Title('Subscription — Waqty')]
class Subscription extends Component
{
    public string $currentKey = 'pro';

    /** @var array<int, array<string, mixed>> */
    public array $plans = [];

    /** @var array<int, array<string, mixed>> */
    public array $usage = [];

    public function mount(): void
    {
        $this->plans = [
            [
                'key' => 'basic',
                'name' => 'أساسي',
                'price' => '$19',
                'descKey' => 'settings.subscription.starterDesc',
                'features' => [
                    'settings.subscription.feat.1branch',
                    'settings.subscription.feat.5emp',
                    'settings.subscription.feat.basicRep',
                    'settings.subscription.feat.emailSup',
                ],
            ],
            [
                'key' => 'pro',
                'name' => 'احترافي',
                'price' => '$49',
                'descKey' => 'settings.subscription.proDesc',
                'features' => [
                    'settings.subscription.feat.3branches',
                    'settings.subscription.feat.15emp',
                    'settings.subscription.feat.advRep',
                    'settings.subscription.feat.prioSup',
                ],
            ],
            [
                'key' => 'enterprise',
                'name' => 'مؤسسي',
                'price' => '$99',
                'descKey' => 'settings.subscription.enterpriseDesc',
                'features' => [
                    'settings.subscription.feat.unlBranches',
                    'settings.subscription.feat.unlEmp',
                    'settings.subscription.feat.customRep',
                    'settings.subscription.feat.support247',
                ],
            ],
        ];

        $this->usage = [
            ['labelKey' => 'settings.subscription.usageBookings', 'used' => 320, 'total' => 500],
            ['labelKey' => 'settings.subscription.usageEmployees', 'used' => 8, 'total' => 15],
            ['labelKey' => 'settings.subscription.usageBranches', 'used' => 2, 'total' => 3],
        ];
    }

    /** @return array<string, mixed> */
    #[Computed]
    public function current(): array
    {
        return collect($this->plans)->firstWhere('key', $this->currentKey) ?? $this->plans[0];
    }

    public function upgrade(string $key): void
    {
        $plan = collect($this->plans)->firstWhere('key', $key);
        if (! $plan || $key === $this->currentKey) {
            return;
        }

        $this->currentKey = $key;
        unset($this->current);

        $this->dispatch('notify', type: 'success', message: __('settings.subscription.toastUpgraded', ['name' => $plan['name']]));
    }

    public function render()
    {
        return view('livewire.settings.subscription');
    }
}
