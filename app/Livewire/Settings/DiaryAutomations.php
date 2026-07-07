<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Data\Waqty\DiaryAutomationData;
use App\Services\Waqty\DiaryAutomationService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Diary Automations — Waqty')]
class DiaryAutomations extends Component
{
    use HandlesWaqtyErrors;

    public bool $showForm = false;

    public ?string $editingUuid = null;

    public string $form_name = '';

    public string $form_trigger = 'booking_created';

    public string $form_action = 'send_sms';

    public bool $form_active = true;

    public bool $showDelete = false;

    public ?string $deletingUuid = null;

    /** @var array<string, bool> */
    public array $overrides = [];

    /** @var array<int, DiaryAutomationData>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    /** @return array<int, DiaryAutomationData> */
    #[Computed]
    public function items(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $this->loaded = app(DiaryAutomationService::class)->list();
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = array_map(fn ($a) => DiaryAutomationData::from($a), $this->fallbackData());
        }

        foreach ($this->loaded as $a) {
            if (isset($this->overrides[$a->uuid])) {
                $a->active = $this->overrides[$a->uuid];
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
        $this->reset(['editingUuid', 'form_name']);
        $this->form_trigger = 'booking_created';
        $this->form_action = 'send_sms';
        $this->form_active = true;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $uuid): void
    {
        $a = collect($this->items())->firstWhere('uuid', $uuid);
        if (! $a) {
            return;
        }
        $this->editingUuid = $uuid;
        $this->form_name = (string) $a->name;
        $this->form_trigger = $a->trigger;
        $this->form_action = $a->action;
        $this->form_active = $a->active;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_name' => ['required', 'string', 'max:60'],
            'form_trigger' => ['required', 'in:booking_created,booking_cancelled,no_show,birthday'],
            'form_action' => ['required', 'in:send_sms,send_email,send_whatsapp,notify_staff'],
        ]);

        $payload = [
            'name' => trim($this->form_name),
            'trigger' => $this->form_trigger,
            'action' => $this->form_action,
            'active' => $this->form_active,
        ];

        $result = $this->waqty(function () use ($payload) {
            $service = app(DiaryAutomationService::class);
            $this->editingUuid
                ? $service->update($this->editingUuid, $payload)
                : $service->create($payload);

            return true;
        }, __('settings.diaryAutomations.createFailed'));

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
        $a = collect($this->items())->firstWhere('uuid', $uuid);
        if (! $a) {
            return;
        }
        $next = ! $a->active;
        $this->overrides[$uuid] = $next;

        if (! $this->usingFallback()) {
            $this->waqty(fn () => app(DiaryAutomationService::class)->update($uuid, ['active' => $next]) ?? true, __('settings.diaryAutomations.updateFailed'));
        }

        unset($this->items);
    }

    public function confirmDelete(string $uuid): void
    {
        $this->deletingUuid = $uuid;
        $this->showDelete = true;
    }

    public function deleteAutomation(): void
    {
        if (! $this->deletingUuid) {
            return;
        }
        $uuid = $this->deletingUuid;
        $result = $this->waqty(fn () => app(DiaryAutomationService::class)->delete($uuid) ?? true, __('settings.diaryAutomations.deleteFailed'));

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
        return view('livewire.settings.diary-automations');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'DA1', 'name' => 'تذكير بالموعد', 'trigger' => 'booking_created', 'action' => 'send_sms', 'active' => true],
            ['uuid' => 'DA2', 'name' => 'رسالة استعادة العميل', 'trigger' => 'no_show', 'action' => 'send_email', 'active' => true],
            ['uuid' => 'DA3', 'name' => 'تهنئة عيد ميلاد', 'trigger' => 'birthday', 'action' => 'send_whatsapp', 'active' => false],
        ];
    }
}
