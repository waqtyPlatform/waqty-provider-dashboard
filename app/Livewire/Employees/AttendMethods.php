<?php

declare(strict_types=1);

namespace App\Livewire\Employees;

use App\Services\Waqty\EmployeeHrService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Attendance Methods — Waqty')]
class AttendMethods extends Component
{
    use HandlesWaqtyErrors;

    // Configure slide-over
    public bool $showConfig = false;

    public ?string $configUuid = null;

    public string $form_type = '';

    public string $form_name = '';

    public string $form_device_ip = '';

    public string $form_device_port = '';

    public string $form_gps_radius = '';

    public string $form_pin_length = '';

    public bool $form_require_approval = false;

    /** Optimistic enabled overrides applied on top of the source rows. @var array<string, bool> */
    public array $overrides = [];

    /** Optimistic config overrides applied on top of the source rows. @var array<string, array<string, mixed>> */
    public array $configOverrides = [];

    /** @var array<int, array<string, mixed>>|null per-request memo */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    /** @return array<int, array<string, mixed>> */
    private function source(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $this->loaded = app(EmployeeHrService::class)->attendanceMethods();
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = $this->fallbackData();
        }

        return $this->loaded;
    }

    public function usingFallback(): bool
    {
        $this->source();

        return $this->fallbackUsed;
    }

    /** Source rows with optimistic enabled/config overrides layered on top. @return array<int, array<string, mixed>> */
    #[Computed]
    public function attendanceMethods(): array
    {
        return array_map(function (array $m) {
            $uuid = (string) ($m['uuid'] ?? '');

            if (array_key_exists($uuid, $this->overrides)) {
                $m['enabled'] = $this->overrides[$uuid];
            }

            if (isset($this->configOverrides[$uuid])) {
                $m = array_merge($m, $this->configOverrides[$uuid]);
            }

            return $m;
        }, $this->source());
    }

    public function toggleAttendanceMethod(string $uuid): void
    {
        $row = collect($this->source())->firstWhere('uuid', $uuid);
        if (! $row) {
            return;
        }

        $current = $this->overrides[$uuid] ?? (bool) ($row['enabled'] ?? false);
        $this->overrides[$uuid] = ! $current;

        if (! $this->usingFallback()) {
            $this->waqty(fn () => app(EmployeeHrService::class)->toggleAttendanceMethod($uuid) ?? true, __('waqty.genericError'));
        }

        unset($this->attendanceMethods);
    }

    public function configure(string $uuid): void
    {
        $m = collect($this->attendanceMethods())->firstWhere('uuid', $uuid);
        if (! $m) {
            return;
        }

        $this->configUuid = $uuid;
        $this->form_type = (string) ($m['type'] ?? '');
        $this->form_name = (string) ($m['name'] ?? '');
        $this->form_device_ip = (string) ($m['device_ip'] ?? '');
        $this->form_device_port = (string) ($m['device_port'] ?? '');
        $this->form_gps_radius = (string) ($m['gps_radius'] ?? '');
        $this->form_pin_length = (string) ($m['pin_length'] ?? '');
        $this->form_require_approval = (bool) ($m['require_approval'] ?? false);
        $this->resetValidation();
        $this->showConfig = true;
    }

    public function saveConfig(): void
    {
        if (! $this->configUuid) {
            return;
        }

        $rules = [];
        $messages = [];
        $override = [];

        switch ($this->form_type) {
            case 'fingerprint':
                $rules = [
                    'form_device_ip' => ['required', 'string', 'max:45'],
                    'form_device_port' => ['required', 'integer', 'min:1', 'max:65535'],
                ];
                $messages = [
                    'form_device_ip.required' => __('emp.attendMethods.deviceIpRequired'),
                    'form_device_port.required' => __('emp.attendMethods.devicePortRequired'),
                ];
                break;
            case 'gps':
                $rules = ['form_gps_radius' => ['required', 'integer', 'min:10', 'max:5000']];
                $messages = ['form_gps_radius.required' => __('emp.attendMethods.gpsRadiusRequired')];
                break;
            case 'pin':
                $rules = ['form_pin_length' => ['required', 'integer', 'min:4', 'max:8']];
                $messages = ['form_pin_length.required' => __('emp.attendMethods.pinLengthRequired')];
                break;
        }

        $this->validate($rules, $messages);

        $payload = ['type' => $this->form_type];

        switch ($this->form_type) {
            case 'fingerprint':
                $override = ['device_ip' => trim($this->form_device_ip), 'device_port' => (int) $this->form_device_port];
                break;
            case 'gps':
                $override = ['gps_radius' => (int) $this->form_gps_radius];
                break;
            case 'pin':
                $override = ['pin_length' => (int) $this->form_pin_length];
                break;
            case 'manual':
                $override = ['require_approval' => $this->form_require_approval];
                break;
        }

        $payload = array_merge($payload, $override);
        $uuid = $this->configUuid;

        $result = $this->waqty(
            fn () => app(EmployeeHrService::class)->updateAttendanceMethod($uuid, $payload) ?? true,
            __('waqty.genericError')
        );

        if ($result || $this->usingFallback()) {
            $this->configOverrides[$uuid] = array_merge($this->configOverrides[$uuid] ?? [], $override);
            $this->showConfig = false;
            $this->configUuid = null;
            unset($this->attendanceMethods);
            $this->dispatch('notify', type: 'success', message: __('common.saved'));
        }
    }

    public function render()
    {
        return view('livewire.employees.attend-methods');
    }

    /** Local Arabic sample capture methods for graceful degradation. @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'AM1', 'type' => 'fingerprint', 'name' => 'البصمة', 'enabled' => true, 'device_ip' => '192.168.1.50', 'device_port' => 4370],
            ['uuid' => 'AM2', 'type' => 'gps', 'name' => 'الموقع الجغرافي (GPS)', 'enabled' => true, 'gps_radius' => 100],
            ['uuid' => 'AM3', 'type' => 'pin', 'name' => 'الرمز السري (PIN)', 'enabled' => false, 'pin_length' => 4],
            ['uuid' => 'AM4', 'type' => 'manual', 'name' => 'التسجيل اليدوي', 'enabled' => true, 'require_approval' => true],
        ];
    }
}
