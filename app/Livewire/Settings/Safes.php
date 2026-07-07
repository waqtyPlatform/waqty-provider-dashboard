<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Data\Waqty\SafeData;
use App\Services\Waqty\SettingsService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use App\Support\Money;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Safes — Waqty')]
class Safes extends Component
{
    use HandlesWaqtyErrors;

    public bool $showForm = false;

    public ?string $editingUuid = null;

    public string $form_name = '';

    public string $form_branch = '';

    public string $form_balance = '0';

    public bool $form_active = true;

    public bool $showDelete = false;

    public ?string $deletingUuid = null;

    /** @var array<string, bool> */
    public array $overrides = [];

    /** @var array<int, SafeData>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    /** @return array<int, SafeData> */
    #[Computed]
    public function items(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $this->loaded = app(SettingsService::class)->safes();
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = array_map(fn ($a) => SafeData::from($a), $this->fallbackData());
        }

        foreach ($this->loaded as $s) {
            if (isset($this->overrides[$s->uuid])) {
                $s->active = $this->overrides[$s->uuid];
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
        $this->reset(['editingUuid', 'form_name', 'form_branch', 'form_balance']);
        $this->form_balance = '0';
        $this->form_active = true;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $uuid): void
    {
        $s = collect($this->items())->firstWhere('uuid', $uuid);
        if (! $s) {
            return;
        }
        $this->editingUuid = $uuid;
        $this->form_name = (string) $s->name;
        $this->form_branch = (string) $s->branch;
        $this->form_balance = (string) Money::fromMinor($s->balance);
        $this->form_active = $s->active;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_name' => ['required', 'string', 'max:60'],
            'form_branch' => ['nullable', 'string', 'max:60'],
            'form_balance' => ['required', 'numeric', 'min:0'],
        ]);

        $payload = [
            'name' => trim($this->form_name),
            'branch' => trim($this->form_branch),
            'balance' => Money::toMinor((float) $this->form_balance),
            'active' => $this->form_active,
        ];

        $result = $this->waqty(function () use ($payload) {
            $service = app(SettingsService::class);
            $this->editingUuid
                ? $service->updateSafe($this->editingUuid, $payload)
                : $service->createSafe($payload);

            return true;
        }, __('settings.safes.createFailed'));

        if ($result || $this->usingFallback()) {
            $this->showForm = false;
            $this->editingUuid = null;
            $this->loaded = null;
            unset($this->items);
            $this->dispatch('notify', type: 'success', message: __('settings.saved'));
        }
    }

    public function toggleActive(string $uuid): void
    {
        $s = collect($this->items())->firstWhere('uuid', $uuid);
        if (! $s) {
            return;
        }
        $next = ! $s->active;
        $this->overrides[$uuid] = $next;

        if (! $this->usingFallback()) {
            $this->waqty(fn () => app(SettingsService::class)->updateSafe($uuid, ['active' => $next]) ?? true, __('settings.safes.updateFailed'));
        }

        unset($this->items);
    }

    public function confirmDelete(string $uuid): void
    {
        $this->deletingUuid = $uuid;
        $this->showDelete = true;
    }

    public function deleteSafe(): void
    {
        if (! $this->deletingUuid) {
            return;
        }
        $uuid = $this->deletingUuid;
        $result = $this->waqty(fn () => app(SettingsService::class)->deleteSafe($uuid) ?? true, __('settings.safes.deleteFailed'));

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
        return view('livewire.settings.safes');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'SF1', 'name' => 'خزنة الاستقبال', 'branch' => 'وسط البلد', 'balance' => 1250000, 'active' => true, 'last_activity' => '2h ago'],
            ['uuid' => 'SF2', 'name' => 'الخزنة الرئيسية', 'branch' => 'وسط البلد', 'balance' => 8400000, 'active' => true, 'last_activity' => '1d ago'],
            ['uuid' => 'SF3', 'name' => 'درج فرع المول', 'branch' => 'مول العرب', 'balance' => 320000, 'active' => false, 'last_activity' => '5d ago'],
        ];
    }
}
