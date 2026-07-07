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
 * Loyalty programme — earning rules plus a tier ladder. GET/PUT
 * `/api/provider/settings/loyalty`; falls back to a default four-tier ladder
 * when the API is unavailable.
 */
#[Layout('components.layouts.app')]
#[Title('Loyalty — Waqty')]
class Loyalty extends Component
{
    use HandlesWaqtyErrors;

    public bool $enabled = true;

    public string $pointsPerEgp = '1';

    public string $pointsPerBooking = '10';

    public string $referralBonus = '100';

    public string $redemptionRate = '100';

    /** @var list<array{name:string, min_points:int, discount:float, color:string}> */
    public array $tiers = [];

    public bool $fallbackUsed = false;

    public function mount(): void
    {
        try {
            $s = app(SettingsService::class)->loyaltySettings();
            $this->enabled = (bool) ($s['enabled'] ?? true);
            $this->pointsPerEgp = (string) ($s['points_per_egp'] ?? '1');
            $this->pointsPerBooking = (string) ($s['points_per_booking'] ?? '10');
            $this->referralBonus = (string) ($s['referral_bonus'] ?? '100');
            $this->redemptionRate = (string) ($s['redemption_rate'] ?? '100');
            $this->tiers = $this->normaliseTiers($s['tiers'] ?? []);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
        }

        if ($this->tiers === []) {
            $this->tiers = $this->defaultTiers();
        }
    }

    public function addTier(): void
    {
        $this->tiers[] = ['name' => '', 'min_points' => 0, 'discount' => 0.0, 'color' => '#00b166'];
    }

    public function removeTier(int $index): void
    {
        unset($this->tiers[$index]);
        $this->tiers = array_values($this->tiers);
    }

    public function save(): void
    {
        $this->validate([
            'pointsPerEgp' => ['required', 'numeric', 'min:0'],
            'pointsPerBooking' => ['required', 'numeric', 'min:0'],
            'referralBonus' => ['required', 'numeric', 'min:0'],
            'redemptionRate' => ['required', 'numeric', 'min:1'],
            'tiers.*.name' => ['required', 'string', 'max:40'],
            'tiers.*.min_points' => ['required', 'integer', 'min:0'],
            'tiers.*.discount' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        if (! $this->fallbackUsed) {
            $this->waqty(fn () => app(SettingsService::class)->updateLoyaltySettings([
                'enabled' => $this->enabled,
                'points_per_egp' => (float) $this->pointsPerEgp,
                'points_per_booking' => (float) $this->pointsPerBooking,
                'referral_bonus' => (float) $this->referralBonus,
                'redemption_rate' => (float) $this->redemptionRate,
                'tiers' => $this->tiers,
            ]) ?? true, __('loyalty.saveFailed'));
        }

        $this->dispatch('notify', type: 'success', message: __('loyalty.saved'));
    }

    public function render()
    {
        return view('livewire.settings.loyalty');
    }

    /**
     * @return list<array{name:string, min_points:int, discount:float, color:string}>
     */
    private function normaliseTiers(mixed $raw): array
    {
        $out = [];
        foreach ((array) $raw as $tier) {
            if (! is_array($tier)) {
                continue;
            }
            $out[] = [
                'name' => (string) ($tier['name'] ?? ''),
                'min_points' => (int) ($tier['min_points'] ?? 0),
                'discount' => (float) ($tier['discount'] ?? 0),
                'color' => (string) ($tier['color'] ?? '#00b166'),
            ];
        }

        return $out;
    }

    /** @return list<array{name:string, min_points:int, discount:float, color:string}> */
    private function defaultTiers(): array
    {
        return [
            ['name' => 'Bronze', 'min_points' => 0, 'discount' => 0.0, 'color' => '#cd7f32'],
            ['name' => 'Silver', 'min_points' => 500, 'discount' => 5.0, 'color' => '#9ca3af'],
            ['name' => 'Gold', 'min_points' => 1500, 'discount' => 10.0, 'color' => '#f59e0b'],
            ['name' => 'Platinum', 'min_points' => 3000, 'discount' => 15.0, 'color' => '#6366f1'],
        ];
    }
}
