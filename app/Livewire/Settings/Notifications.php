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
 * Notification preferences — a fixed matrix of 6 event types × (push, email).
 * GET/PUT `/api/provider/settings/notifications`; falls back to sensible
 * defaults when the API is unavailable (save is then a local no-op toast).
 */
#[Layout('components.layouts.app')]
#[Title('Notifications — Waqty')]
class Notifications extends Component
{
    use HandlesWaqtyErrors;

    /** @var list<string> the 6 fixed notification event types */
    public array $types = [
        'newBooking',
        'cancelBooking',
        'paymentReceived',
        'dailySummary',
        'employeeClockIn',
        'clientBirthday',
    ];

    /** @var array<string, array{push:bool, email:bool}> */
    public array $prefs = [];

    public bool $fallbackUsed = false;

    public function mount(): void
    {
        try {
            foreach (app(SettingsService::class)->notificationSettings() as $row) {
                $type = is_array($row) ? ($row['type'] ?? null) : null;
                if (is_string($type) && in_array($type, $this->types, true)) {
                    $this->prefs[$type] = [
                        'push' => (bool) ($row['push'] ?? false),
                        'email' => (bool) ($row['email'] ?? false),
                    ];
                }
            }
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
        }

        // Fill any type the API omitted (or all of them, in fallback mode).
        foreach ($this->types as $type) {
            $this->prefs[$type] ??= $this->defaultPref($type);
        }
    }

    public function save(): void
    {
        if (! $this->fallbackUsed) {
            $settings = [];
            foreach ($this->types as $type) {
                $settings[] = [
                    'type' => $type,
                    'push' => $this->prefs[$type]['push'],
                    'email' => $this->prefs[$type]['email'],
                ];
            }

            $this->waqty(
                fn () => app(SettingsService::class)->updateNotificationSettings($settings) ?? true,
                __('settings.notifications.saveFailed'),
            );
        }

        $this->dispatch('notify', type: 'success', message: __('settings.notifications.saved'));
    }

    public function render()
    {
        return view('livewire.settings.notifications');
    }

    /** @return array{push:bool, email:bool} */
    private function defaultPref(string $type): array
    {
        // Summaries and birthdays default to email; operational alerts to push.
        return match ($type) {
            'dailySummary', 'clientBirthday' => ['push' => false, 'email' => true],
            default => ['push' => true, 'email' => false],
        };
    }
}
