<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Data\Waqty\FingerprintDeviceData;
use App\Services\Waqty\FingerprintDeviceService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Fingerprint Devices — Waqty')]
class FingerprintDevices extends Component
{
    use HandlesWaqtyErrors;

    public bool $showForm = false;

    public ?string $editingUuid = null;

    public string $form_name = '';

    public ?string $form_ip = '';

    public int $form_port = 4370;

    public bool $form_active = true;

    public bool $showDelete = false;

    public ?string $deletingUuid = null;

    /** @var array<string, bool> */
    public array $overrides = [];

    /** @var array<int, FingerprintDeviceData>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    /** @return array<int, FingerprintDeviceData> */
    #[Computed]
    public function items(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $this->loaded = app(FingerprintDeviceService::class)->list();
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = array_map(fn ($a) => FingerprintDeviceData::from($a), $this->fallbackData());
        }

        foreach ($this->loaded as $d) {
            if (isset($this->overrides[$d->uuid])) {
                $d->active = $this->overrides[$d->uuid];
            }
        }

        return $this->loaded;
    }

    public function usingFallback(): bool
    {
        $this->items();

        return $this->fallbackUsed;
    }

    public function openCreate(): void
    {
        $this->reset(['editingUuid', 'form_name', 'form_ip', 'form_port']);
        $this->form_active = true;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $uuid): void
    {
        $d = collect($this->items())->firstWhere('uuid', $uuid);
        if (! $d) {
            return;
        }
        $this->editingUuid = $uuid;
        $this->form_name = (string) $d->name;
        $this->form_ip = (string) $d->ip_address;
        $this->form_port = $d->port;
        $this->form_active = $d->active;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        if (trim((string) $this->form_ip) === '') {
            $this->form_ip = null;
        }

        $this->validate([
            'form_name' => ['required', 'string', 'max:60'],
            'form_ip' => ['nullable', 'ipv4'],
            'form_port' => ['required', 'integer', 'min:1', 'max:65535'],
        ]);

        $payload = [
            'name' => trim($this->form_name),
            'ip_address' => $this->form_ip !== null ? trim($this->form_ip) : null,
            'port' => (int) $this->form_port,
            'active' => $this->form_active,
        ];

        $result = $this->waqty(function () use ($payload) {
            $service = app(FingerprintDeviceService::class);
            $this->editingUuid
                ? $service->update($this->editingUuid, $payload)
                : $service->create($payload);

            return true;
        }, __('settings.fpDevices.saveFailed'));

        if ($result || $this->usingFallback()) {
            $this->showForm = false;
            $this->editingUuid = null;
            $this->loaded = null;
            unset($this->items);
            $this->dispatch('notify', type: 'success', message: __('common.saved'));
        }
    }

    public function toggleActive(string $uuid): void
    {
        $d = collect($this->items())->firstWhere('uuid', $uuid);
        if (! $d) {
            return;
        }
        $next = ! $d->active;
        $this->overrides[$uuid] = $next;

        if (! $this->usingFallback()) {
            $this->waqty(fn () => app(FingerprintDeviceService::class)->update($uuid, ['active' => $next]) ?? true, __('settings.fpDevices.updateFailed'));
        }

        unset($this->items);
    }

    public function confirmDelete(string $uuid): void
    {
        $this->deletingUuid = $uuid;
        $this->showDelete = true;
    }

    public function deleteDevice(): void
    {
        if (! $this->deletingUuid) {
            return;
        }
        $uuid = $this->deletingUuid;
        $result = $this->waqty(fn () => app(FingerprintDeviceService::class)->delete($uuid) ?? true, __('settings.fpDevices.deleteFailed'));

        $this->showDelete = false;
        $this->deletingUuid = null;

        if ($result || $this->usingFallback()) {
            $this->loaded = null;
            unset($this->items);
            $this->dispatch('notify', type: 'success', message: __('common.deleted'));
        }
    }

    public function render()
    {
        return view('livewire.settings.fingerprint-devices');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'FP1', 'name' => 'جهاز الباب الأمامي ZKTeco', 'ip_address' => '192.168.1.50', 'port' => 4370, 'active' => true],
            ['uuid' => 'FP2', 'name' => 'مدخل الموظفين', 'ip_address' => '192.168.1.51', 'port' => 4370, 'active' => true],
            ['uuid' => 'FP3', 'name' => 'المكتب الخلفي', 'ip_address' => '192.168.1.52', 'port' => 4370, 'active' => false],
        ];
    }
}
