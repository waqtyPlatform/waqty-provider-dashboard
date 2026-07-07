<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Services\Waqty\BranchSettingsService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Settings › Branch detail — tabbed general / rooms / geofence editor.
 * General + geofence PUT to /api/provider/branches/{uuid}; rooms are local
 * (no live endpoint). Falls back to a sample branch when offline.
 */
#[Layout('components.layouts.app')]
#[Title('Branch — Waqty')]
class BranchDetail extends Component
{
    use HandlesWaqtyErrors;

    public string $uuid = '';

    public string $tab = 'general';

    public bool $fallbackUsed = false;

    // General
    public string $form_name = '';

    public string $form_phone = '';

    public string $form_city = '';

    public string $form_email = '';

    // Geofence
    public ?string $form_latitude = null;

    public ?string $form_longitude = null;

    public int $form_radius = 100;

    public bool $form_require_gps = false;

    // Rooms (local)
    /** @var array<int, array{id:string, name:string, capacity:int}> */
    public array $rooms = [];

    public string $room_name = '';

    public int $room_capacity = 1;

    public function mount(string $uuid): void
    {
        $this->uuid = $uuid;

        try {
            $branch = app(BranchSettingsService::class)->get($uuid);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $branch = null;
        }

        if ($branch === null || blank($branch->uuid)) {
            $this->fallbackUsed = true;
            $this->hydrateFallback();

            return;
        }

        $this->form_name = (string) $branch->name;
        $this->form_phone = (string) $branch->phone;
        $this->form_city = (string) $branch->city;
        $this->form_latitude = $branch->latitude !== null ? (string) $branch->latitude : null;
        $this->form_longitude = $branch->longitude !== null ? (string) $branch->longitude : null;
        $this->form_radius = $branch->geofence_radius ?? 100;
        $this->form_require_gps = $branch->require_gps;
        $this->rooms = $this->sampleRooms();
    }

    public function usingFallback(): bool
    {
        return $this->fallbackUsed;
    }

    public function saveGeneral(): void
    {
        $this->validate([
            'form_name' => ['required', 'string', 'max:100'],
            'form_phone' => ['nullable', 'string', 'max:30'],
            'form_city' => ['nullable', 'string', 'max:100'],
            'form_email' => ['nullable', 'email', 'max:120'],
        ]);

        $payload = [
            'name' => trim($this->form_name),
            'phone' => trim($this->form_phone),
            'city' => trim($this->form_city),
            'email' => trim($this->form_email) ?: null,
        ];

        $this->persist($payload);
    }

    public function saveGeofence(): void
    {
        $this->validate([
            'form_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'form_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'form_radius' => ['required', 'integer', 'min:10', 'max:5000'],
        ]);

        $payload = [
            'latitude' => $this->form_latitude !== null && $this->form_latitude !== '' ? (float) $this->form_latitude : null,
            'longitude' => $this->form_longitude !== null && $this->form_longitude !== '' ? (float) $this->form_longitude : null,
            'geofence_radius' => $this->form_radius,
            'require_gps' => $this->form_require_gps,
        ];

        $this->persist($payload);
    }

    /** @param array<string, mixed> $payload */
    private function persist(array $payload): void
    {
        if (! $this->usingFallback()) {
            $result = $this->waqty(fn () => app(BranchSettingsService::class)->update($this->uuid, $payload) ?? true, __('settings.branches.createFailed'));
            if (! $result) {
                return;
            }
        }

        $this->dispatch('notify', type: 'success', message: __('settings.branches.updated'));
    }

    public function addRoom(): void
    {
        $this->validate(['room_name' => ['required', 'string', 'max:60'], 'room_capacity' => ['required', 'integer', 'min:1', 'max:100']]);

        $this->rooms[] = [
            'id' => 'room-'.(count($this->rooms) + 1).'-'.substr(md5($this->room_name), 0, 4),
            'name' => trim($this->room_name),
            'capacity' => $this->room_capacity,
        ];
        $this->reset(['room_name']);
        $this->room_capacity = 1;
        $this->dispatch('notify', type: 'success', message: __('settings.saved'));
    }

    public function removeRoom(string $id): void
    {
        $this->rooms = array_values(array_filter($this->rooms, fn ($r) => $r['id'] !== $id));
    }

    public function render()
    {
        return view('livewire.settings.branch-detail');
    }

    private function hydrateFallback(): void
    {
        $this->form_name = 'فرع وسط البلد';
        $this->form_phone = '011 2345 6789';
        $this->form_city = 'القاهرة';
        $this->form_email = 'downtown@waqty.com';
        $this->form_latitude = '30.0444';
        $this->form_longitude = '31.2357';
        $this->form_radius = 150;
        $this->form_require_gps = true;
        $this->rooms = $this->sampleRooms();
    }

    /** @return array<int, array{id:string, name:string, capacity:int}> */
    private function sampleRooms(): array
    {
        return [
            ['id' => 'room-1', 'name' => 'جناح أ', 'capacity' => 2],
            ['id' => 'room-2', 'name' => 'غرفة العلاج 1', 'capacity' => 1],
            ['id' => 'room-3', 'name' => 'استوديو جماعي', 'capacity' => 8],
        ];
    }
}
