<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Services\Waqty\BranchSettingsService;
use App\Services\Waqty\EmployeeService;
use App\Services\Waqty\PricingGroupService;
use App\Services\Waqty\ServiceCatalogService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use App\Support\Money;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Settings › Service Pricing — scoped price overrides.
 * Base prices cascade; branch / employee / pricing-group scopes override them
 * (the read-side of ServiceCatalogService::resolveLocalPrice). Pick a scope +
 * target, edit per-service prices (EGP), save. Falls back to sample data.
 */
#[Layout('components.layouts.app')]
#[Title('Service Pricing — Waqty')]
class ServicePricing extends Component
{
    use HandlesWaqtyErrors;

    public const SCOPES = ['base', 'branch', 'employee', 'group'];

    public string $scope = 'base';

    public ?string $scopeId = null;

    /** @var array<int, array{uuid:string, name:string}> */
    public array $services = [];

    /** @var array<int, array{uuid:string, name:string}> */
    public array $branches = [];

    /** @var array<int, array{uuid:string, name:string}> */
    public array $employees = [];

    /** @var array<int, array{uuid:string, name:string}> */
    public array $groups = [];

    /** Existing price rows (minor units). @var array<int, array<string, mixed>> */
    public array $priceRows = [];

    /** service_uuid => price string in EGP for the current scope. @var array<string, string> */
    public array $edits = [];

    public bool $fallbackUsed = false;

    public function mount(): void
    {
        try {
            $this->services = array_map(fn ($s) => ['uuid' => (string) $s->uuid, 'name' => (string) $s->name], app(ServiceCatalogService::class)->services([], false));
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
        }

        if ($this->fallbackUsed || $this->services === []) {
            $this->loadFallback();
        } else {
            $this->branches = $this->safeList(fn () => app(BranchSettingsService::class)->list());
            $this->employees = $this->safeList(fn () => app(EmployeeService::class)->employees());
            $this->groups = $this->safeList(fn () => app(PricingGroupService::class)->list());
            $this->priceRows = $this->safeRows(fn () => app(ServiceCatalogService::class)->servicePrices());
        }

        $this->loadEdits();
    }

    public function usingFallback(): bool
    {
        return $this->fallbackUsed;
    }

    /** @param callable():array<int, object> $fn @return array<int, array{uuid:string, name:string}> */
    private function safeList(callable $fn): array
    {
        try {
            return array_map(fn ($o) => ['uuid' => (string) $o->uuid, 'name' => (string) $o->name], $fn());
        } catch (WaqtyApiException) {
            return [];
        }
    }

    /** @param callable():array<int, object> $fn @return array<int, array<string, mixed>> */
    private function safeRows(callable $fn): array
    {
        try {
            return array_map(fn ($p) => [
                'uuid' => $p->uuid,
                'service_uuid' => $p->service_uuid,
                'branch_uuid' => $p->branch_uuid,
                'employee_uuid' => $p->employee_uuid,
                'pricing_group_uuid' => $p->pricing_group_uuid,
                'price' => $p->price,
            ], $fn());
        } catch (WaqtyApiException) {
            return [];
        }
    }

    public function updatedScope(): void
    {
        $this->scopeId = null;
        $this->loadEdits();
    }

    public function updatedScopeId(): void
    {
        $this->loadEdits();
    }

    /** Options for the target selector under the current scope. @return array<string, string> */
    public function targetOptions(): array
    {
        $list = match ($this->scope) {
            'branch' => $this->branches,
            'employee' => $this->employees,
            'group' => $this->groups,
            default => [],
        };

        return collect($list)->pluck('name', 'uuid')->all();
    }

    /** Rebuild the editable price map for the active scope + target. */
    private function loadEdits(): void
    {
        $this->edits = [];
        foreach ($this->services as $s) {
            $row = $this->findRow($s['uuid']);
            $this->edits[$s['uuid']] = $row !== null ? (string) Money::fromMinor((int) $row['price']) : '';
        }
    }

    /** The price row matching the active scope for a service, if any. */
    private function findRow(string $serviceUuid): ?array
    {
        foreach ($this->priceRows as $row) {
            if (($row['service_uuid'] ?? null) !== $serviceUuid) {
                continue;
            }
            $matches = match ($this->scope) {
                'base' => blank($row['branch_uuid']) && blank($row['employee_uuid']) && blank($row['pricing_group_uuid']),
                'branch' => ($row['branch_uuid'] ?? null) === $this->scopeId,
                'employee' => ($row['employee_uuid'] ?? null) === $this->scopeId,
                'group' => ($row['pricing_group_uuid'] ?? null) === $this->scopeId,
                default => false,
            };
            if ($matches) {
                return $row;
            }
        }

        return null;
    }

    /** Base price for a service, shown as the fallback hint on overrides. */
    public function basePrice(string $serviceUuid): ?int
    {
        foreach ($this->priceRows as $row) {
            if (($row['service_uuid'] ?? null) === $serviceUuid
                && blank($row['branch_uuid']) && blank($row['employee_uuid']) && blank($row['pricing_group_uuid'])) {
                return (int) $row['price'];
            }
        }

        return null;
    }

    public function save(): void
    {
        if ($this->scope !== 'base' && ! $this->scopeId) {
            $this->dispatch('notify', type: 'warning', message: __('settings.servicePricing.pickTarget'));

            return;
        }

        $scopeKey = match ($this->scope) {
            'branch' => 'branch_uuid',
            'employee' => 'employee_uuid',
            'group' => 'pricing_group_uuid',
            default => null,
        };

        if (! $this->usingFallback()) {
            foreach ($this->edits as $serviceUuid => $priceStr) {
                if (trim((string) $priceStr) === '') {
                    continue;
                }
                $existing = $this->findRow($serviceUuid);
                $payload = array_filter([
                    'uuid' => $existing['uuid'] ?? null,
                    'service_uuid' => $serviceUuid,
                    $scopeKey => $scopeKey ? $this->scopeId : null,
                    'price' => Money::toMinor((float) $priceStr),
                ], fn ($v) => $v !== null);

                $this->waqty(fn () => app(ServiceCatalogService::class)->upsertServicePrice($payload) ?? true, __('waqty.genericError'));
            }
            // Refresh rows so subsequent edits reuse the new uuids.
            $this->priceRows = $this->safeRows(fn () => app(ServiceCatalogService::class)->servicePrices());
        } else {
            $this->applyEditsLocally($scopeKey);
        }

        $this->dispatch('notify', type: 'success', message: __('settings.saved'));
    }

    /** In fallback mode, mutate the in-memory rows so the UI reflects the change. */
    private function applyEditsLocally(?string $scopeKey): void
    {
        foreach ($this->edits as $serviceUuid => $priceStr) {
            if (trim((string) $priceStr) === '') {
                continue;
            }
            $minor = Money::toMinor((float) $priceStr);
            $existing = $this->findRow($serviceUuid);
            if ($existing !== null) {
                foreach ($this->priceRows as $i => $row) {
                    if (($row['uuid'] ?? null) === $existing['uuid']) {
                        $this->priceRows[$i]['price'] = $minor;
                    }
                }
            } else {
                $this->priceRows[] = [
                    'uuid' => 'local-'.substr(md5($serviceUuid.$this->scope.$this->scopeId), 0, 6),
                    'service_uuid' => $serviceUuid,
                    'branch_uuid' => $scopeKey === 'branch_uuid' ? $this->scopeId : null,
                    'employee_uuid' => $scopeKey === 'employee_uuid' ? $this->scopeId : null,
                    'pricing_group_uuid' => $scopeKey === 'pricing_group_uuid' ? $this->scopeId : null,
                    'price' => $minor,
                ];
            }
        }
    }

    public function render()
    {
        return view('livewire.settings.service-pricing');
    }

    private function loadFallback(): void
    {
        $this->fallbackUsed = true;
        $this->services = [
            ['uuid' => 'S1', 'name' => 'قصّة شعر كلاسيك'],
            ['uuid' => 'S2', 'name' => 'صبغة شعر'],
            ['uuid' => 'S3', 'name' => 'مانيكير'],
            ['uuid' => 'S4', 'name' => 'مساج الأنسجة العميقة'],
        ];
        $this->branches = [
            ['uuid' => 'BR1', 'name' => 'فرع وسط البلد'],
            ['uuid' => 'BR2', 'name' => 'مول العرب'],
        ];
        $this->employees = [
            ['uuid' => 'E1', 'name' => 'سارة أحمد'],
            ['uuid' => 'E2', 'name' => 'عمر خالد'],
        ];
        $this->groups = [
            ['uuid' => 'G1', 'name' => 'كبار الشخصيات'],
            ['uuid' => 'G2', 'name' => 'الشركات'],
        ];
        $this->priceRows = [
            ['uuid' => 'PB1', 'service_uuid' => 'S1', 'branch_uuid' => null, 'employee_uuid' => null, 'pricing_group_uuid' => null, 'price' => 15000],
            ['uuid' => 'PB2', 'service_uuid' => 'S2', 'branch_uuid' => null, 'employee_uuid' => null, 'pricing_group_uuid' => null, 'price' => 45000],
            ['uuid' => 'PB3', 'service_uuid' => 'S3', 'branch_uuid' => null, 'employee_uuid' => null, 'pricing_group_uuid' => null, 'price' => 20000],
            ['uuid' => 'PB4', 'service_uuid' => 'S4', 'branch_uuid' => null, 'employee_uuid' => null, 'pricing_group_uuid' => null, 'price' => 55000],
            ['uuid' => 'PV1', 'service_uuid' => 'S1', 'branch_uuid' => null, 'employee_uuid' => null, 'pricing_group_uuid' => 'G1', 'price' => 20000],
        ];
    }
}
