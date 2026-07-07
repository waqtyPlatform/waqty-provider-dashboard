<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Data\Waqty\PaymentMethodData;
use App\Services\Waqty\SettingsService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Payment Methods — Waqty')]
class PaymentMethods extends Component
{
    use HandlesWaqtyErrors;

    public bool $showForm = false;

    public ?string $editingUuid = null;

    public string $form_name = '';

    public string $form_type = 'cash';

    public string $form_fee = '0';

    public bool $form_active = true;

    public bool $showDelete = false;

    public ?string $deletingUuid = null;

    /** @var array<string, bool> */
    public array $overrides = [];

    /** @var array<int, PaymentMethodData>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    /** @return array<int, PaymentMethodData> */
    #[Computed]
    public function methods(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $this->loaded = app(SettingsService::class)->paymentMethods();
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = array_map(fn ($a) => PaymentMethodData::from($a), $this->fallbackData());
        }

        foreach ($this->loaded as $m) {
            if (isset($this->overrides[$m->uuid])) {
                $m->active = $this->overrides[$m->uuid];
            }
        }

        return $this->loaded;
    }

    public function usingFallback(): bool
    {
        $this->methods();

        return $this->fallbackUsed;
    }

    public function openCreate(): void
    {
        $this->reset(['editingUuid', 'form_name', 'form_fee']);
        $this->form_type = 'cash';
        $this->form_active = true;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $uuid): void
    {
        $m = collect($this->methods())->firstWhere('uuid', $uuid);
        if (! $m) {
            return;
        }
        $this->editingUuid = $uuid;
        $this->form_name = (string) $m->name;
        $this->form_type = $m->type;
        $this->form_fee = (string) $m->fee_percentage;
        $this->form_active = $m->active;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_name' => ['required', 'string', 'max:60'],
            'form_type' => ['required', 'in:cash,card,wallet,bank_transfer'],
            'form_fee' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $payload = [
            'name' => trim($this->form_name),
            'type' => $this->form_type,
            'fee_percentage' => (float) $this->form_fee,
            'active' => $this->form_active,
        ];

        $service = app(SettingsService::class);
        $result = $this->waqty(function () use ($service, $payload) {
            $this->editingUuid
                ? $service->updatePaymentMethod($this->editingUuid, $payload)
                : $service->createPaymentMethod($payload);

            return true;
        }, __('waqty.genericError'));

        if ($result || $this->usingFallback()) {
            $this->showForm = false;
            $this->editingUuid = null;
            $this->loaded = null;
            unset($this->methods);
            $this->dispatch('notify', type: 'success', message: __('settings.saved'));
        }
    }

    public function toggleActive(string $uuid): void
    {
        $m = collect($this->methods())->firstWhere('uuid', $uuid);
        if (! $m) {
            return;
        }
        $next = ! $m->active;
        $this->overrides[$uuid] = $next;

        if (! $this->usingFallback()) {
            $this->waqty(fn () => app(SettingsService::class)->updatePaymentMethod($uuid, ['active' => $next]) ?? true, __('waqty.genericError'));
        }

        unset($this->methods);
    }

    public function confirmDelete(string $uuid): void
    {
        $this->deletingUuid = $uuid;
        $this->showDelete = true;
    }

    public function deleteMethod(): void
    {
        if (! $this->deletingUuid) {
            return;
        }
        $uuid = $this->deletingUuid;
        $result = $this->waqty(fn () => app(SettingsService::class)->deletePaymentMethod($uuid) ?? true, __('waqty.genericError'));

        $this->showDelete = false;
        $this->deletingUuid = null;

        if ($result || $this->usingFallback()) {
            $this->loaded = null;
            unset($this->methods);
            $this->dispatch('notify', type: 'success', message: __('common.deleted'));
        }
    }

    public function render()
    {
        return view('livewire.settings.payment-methods');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'PM1', 'name' => 'نقدي', 'type' => 'cash', 'fee_percentage' => 0, 'fee_fixed' => 0, 'active' => true],
            ['uuid' => 'PM2', 'name' => 'Visa / Mastercard', 'type' => 'card', 'fee_percentage' => 2.5, 'fee_fixed' => 0, 'active' => true],
            ['uuid' => 'PM3', 'name' => 'فودافون كاش', 'type' => 'wallet', 'fee_percentage' => 1.0, 'fee_fixed' => 0, 'active' => true],
            ['uuid' => 'PM4', 'name' => 'تحويل بنكي', 'type' => 'bank_transfer', 'fee_percentage' => 0, 'fee_fixed' => 0, 'active' => false],
        ];
    }
}
