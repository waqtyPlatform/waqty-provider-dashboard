<?php

declare(strict_types=1);

namespace App\Livewire\Employees;

use App\Enums\UserRole;
use App\Services\Waqty\EmployeeHrService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use App\Support\CurrentProvider;
use App\Support\Money;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Employees › Commission settings — an admin-only console for the commission
 * engine. General settings (enable, base rate, payout cycle) are saved together
 * via "Save all", while each rule is CRUD'd through EmployeeHrService
 * (commissionApi). Rules come in three kinds shown as tabs: per-service rates,
 * target tiers (sales threshold → payout multiplier) and customer-segment
 * adjustments. Falls back to local Arabic sample rules when the API is down.
 */
#[Layout('components.layouts.app')]
#[Title('Commission Settings — Waqty')]
class CommissionSettings extends Component
{
    use HandlesWaqtyErrors;

    /** Rule kinds, one per tab (English enum values). */
    public const KINDS = ['service', 'tier', 'segment'];

    public string $tab = 'service';

    // -- General settings (saved together via saveAll) -------------------
    public bool $enabled = true;

    public string $baseRate = '10';

    public string $payoutCycle = 'monthly'; // monthly | biweekly | weekly

    // -- Create / edit slide-over ----------------------------------------
    public bool $showForm = false;

    public ?string $editingUuid = null;

    public string $form_kind = 'service';

    public string $form_label = '';

    public string $form_rate = '';

    public string $form_threshold = '';

    public string $form_multiplier = '1.25';

    // -- Delete confirmation ---------------------------------------------
    public bool $showDelete = false;

    public ?string $deletingUuid = null;

    /** Optimistic active-state overrides, keyed by rule uuid. @var array<string, bool> */
    public array $overrides = [];

    /** @var array<int, array<string, mixed>>|null per-request memo */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    public function isAdmin(): bool
    {
        return app(CurrentProvider::class)->role() === UserRole::Admin;
    }

    /** @return array<int, array<string, mixed>> */
    #[Computed]
    public function items(): array
    {
        if ($this->loaded === null) {
            try {
                $rows = app(EmployeeHrService::class)->commissionRules();
            } catch (WaqtyApiException) {
                $this->fallbackUsed = true;
                $rows = $this->fallbackData();
            }

            $this->loaded = array_map(fn ($r) => $this->normalize(is_array($r) ? $r : []), $rows);
        }

        // Re-apply optimistic toggles on every read so the switch stays in sync.
        foreach ($this->loaded as $i => $row) {
            if (array_key_exists($row['uuid'], $this->overrides)) {
                $this->loaded[$i]['active'] = $this->overrides[$row['uuid']];
            }
        }

        return $this->loaded;
    }

    public function usingFallback(): bool
    {
        $this->items();

        return $this->fallbackUsed;
    }

    /** Rules for the active tab. @return array<int, array<string, mixed>> */
    #[Computed]
    public function visibleRules(): array
    {
        return array_values(array_filter($this->items(), fn ($r) => $r['kind'] === $this->tab));
    }

    /** @return array{total:int, active:int, services:int} */
    #[Computed]
    public function kpis(): array
    {
        $items = $this->items();

        return [
            'total' => count($items),
            'active' => count(array_filter($items, fn ($r) => $r['active'])),
            'services' => count(array_filter($items, fn ($r) => $r['kind'] === 'service')),
        ];
    }

    public function openCreate(string $kind): void
    {
        $this->reset(['editingUuid', 'form_label', 'form_rate', 'form_threshold']);
        $this->form_kind = in_array($kind, self::KINDS, true) ? $kind : 'service';
        $this->form_multiplier = '1.25';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $uuid): void
    {
        $r = collect($this->items())->firstWhere('uuid', $uuid);
        if (! $r) {
            return;
        }

        $this->editingUuid = $uuid;
        $this->form_kind = (string) $r['kind'];
        $this->form_label = (string) $r['label'];
        $this->form_rate = (string) $r['rate'];
        $this->form_threshold = (string) Money::fromMinor((int) $r['threshold']);
        $this->form_multiplier = (string) $r['multiplier'];
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $rules = ['form_label' => ['required', 'string', 'max:80']];
        $messages = ['form_label.required' => __('emp.commissions.nameRequired')];

        if ($this->form_kind === 'tier') {
            $rules['form_threshold'] = ['required', 'numeric', 'min:0'];
            $rules['form_multiplier'] = ['required', 'numeric', 'min:1'];
            $messages += [
                'form_threshold.required' => __('emp.commissions.thresholdRequired'),
                'form_multiplier.required' => __('emp.commissions.multiplierRequired'),
                'form_multiplier.min' => __('emp.commissions.multiplierMin'),
            ];
        } else {
            $rules['form_rate'] = ['required', 'numeric', 'min:0', 'max:100'];
            $messages += [
                'form_rate.required' => __('emp.commissions.rateRequired'),
                'form_rate.min' => __('emp.commissions.rateRange'),
                'form_rate.max' => __('emp.commissions.rateRange'),
            ];
        }

        $this->validate($rules, $messages);

        $payload = ['type' => $this->form_kind, 'name' => trim($this->form_label), 'active' => true];
        if ($this->form_kind === 'tier') {
            $payload['threshold'] = Money::toMinor((float) $this->form_threshold);
            $payload['multiplier'] = (float) $this->form_multiplier;
        } else {
            $payload['rate'] = (float) $this->form_rate;
        }

        $result = $this->waqty(function () use ($payload) {
            $service = app(EmployeeHrService::class);
            $this->editingUuid
                ? $service->updateCommissionRule($this->editingUuid, $payload)
                : $service->createCommissionRule($payload);

            return true;
        }, __('emp.commissions.saveFailed'));

        if ($result || $this->usingFallback()) {
            $this->showForm = false;
            $this->editingUuid = null;
            $this->loaded = null;
            unset($this->items, $this->visibleRules, $this->kpis);
            $this->dispatch('notify', type: 'success', message: __('common.saved'));
        }
    }

    public function toggleActive(string $uuid): void
    {
        $r = collect($this->items())->firstWhere('uuid', $uuid);
        if (! $r) {
            return;
        }
        $next = ! $r['active'];
        $this->overrides[$uuid] = $next;

        if (! $this->usingFallback()) {
            $this->waqty(fn () => app(EmployeeHrService::class)->updateCommissionRule($uuid, ['active' => $next]) ?? true, __('emp.commissions.saveFailed'));
        }

        unset($this->items, $this->visibleRules, $this->kpis);
    }

    public function confirmDelete(string $uuid): void
    {
        $this->deletingUuid = $uuid;
        $this->showDelete = true;
    }

    public function deleteRule(): void
    {
        if (! $this->deletingUuid) {
            return;
        }

        $uuid = $this->deletingUuid;
        $result = $this->waqty(fn () => app(EmployeeHrService::class)->deleteCommissionRule($uuid) ?? true, __('emp.commissions.deleteFailed'));

        $this->showDelete = false;
        $this->deletingUuid = null;

        if ($result || $this->usingFallback()) {
            $this->loaded = null;
            unset($this->items, $this->visibleRules, $this->kpis);
            $this->dispatch('notify', type: 'success', message: __('common.deleted'));
        }
    }

    /**
     * Persist the general settings. There is no bulk endpoint, so this keeps
     * the config in component state (a local demo) after validating the base
     * rate, then notifies — mirroring the "save all" primary on the page.
     */
    public function saveAll(): void
    {
        $this->validate([
            'baseRate' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [
            'baseRate.required' => __('emp.commissions.rateRequired'),
            'baseRate.min' => __('emp.commissions.baseRateRange'),
            'baseRate.max' => __('emp.commissions.baseRateRange'),
        ]);

        $this->dispatch('notify', type: 'success', message: __('emp.commissions.settingsSaved'));
    }

    public function render()
    {
        return view('livewire.employees.commission-settings');
    }

    /**
     * Shape a raw rule row into the columns this screen renders.
     *
     * @param  array<string, mixed>  $r
     * @return array<string, mixed>
     */
    private function normalize(array $r): array
    {
        $kind = (string) ($r['type'] ?? $r['kind'] ?? 'service');
        if (! in_array($kind, self::KINDS, true)) {
            $kind = 'service';
        }

        return [
            'uuid' => (string) ($r['uuid'] ?? $r['id'] ?? ''),
            'kind' => $kind,
            'label' => (string) ($r['name'] ?? $r['label'] ?? ''),
            'rate' => (float) ($r['rate'] ?? 0),
            'threshold' => (int) ($r['threshold'] ?? 0),
            'multiplier' => (float) ($r['multiplier'] ?? $r['tier_multiplier'] ?? 1),
            'active' => (bool) ($r['active'] ?? true),
        ];
    }

    /**
     * Local Arabic sample commission rules for graceful degradation.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'CR1', 'type' => 'service', 'name' => 'قص وتصفيف الشعر', 'rate' => 10, 'active' => true],
            ['uuid' => 'CR2', 'type' => 'service', 'name' => 'صبغة الشعر', 'rate' => 15, 'active' => true],
            ['uuid' => 'CR3', 'type' => 'tier', 'name' => 'تجاوز 30,000 جنيه', 'threshold' => 3000000, 'multiplier' => 1.25, 'active' => true],
            ['uuid' => 'CR4', 'type' => 'tier', 'name' => 'تجاوز 50,000 جنيه', 'threshold' => 5000000, 'multiplier' => 1.5, 'active' => false],
            ['uuid' => 'CR5', 'type' => 'segment', 'name' => 'عملاء VIP', 'rate' => 5, 'active' => true],
            ['uuid' => 'CR6', 'type' => 'segment', 'name' => 'عملاء جدد', 'rate' => 3, 'active' => true],
        ];
    }
}
