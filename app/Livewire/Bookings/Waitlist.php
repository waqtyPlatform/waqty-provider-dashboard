<?php

declare(strict_types=1);

namespace App\Livewire\Bookings;

use App\Data\Waqty\WaitlistData;
use App\Services\Waqty\WaitlistService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Waitlist — Waqty')]
class Waitlist extends Component
{
    use HandlesWaqtyErrors;

    public string $statusFilter = 'all';

    // Remove confirmation
    public bool $showDelete = false;

    public ?string $deletingUuid = null;

    /** Optimistic status overrides applied on top of the fetched list. @var array<string, string> */
    public array $overrides = [];

    /** @var array<int, WaitlistData>|null per-request memo */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    /** @return array<int, WaitlistData> */
    private function source(): array
    {
        if ($this->loaded !== null) {
            return $this->applyOverrides($this->loaded);
        }

        try {
            $filters = [];
            if ($this->statusFilter !== 'all') {
                $filters['status'] = $this->statusFilter;
            }
            $this->loaded = app(WaitlistService::class)->list($filters);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = array_map(fn ($a) => WaitlistData::from($a), $this->fallbackData());
        }

        // Sort by queue position ascending.
        usort($this->loaded, fn (WaitlistData $a, WaitlistData $b) => $a->position <=> $b->position);

        return $this->applyOverrides($this->loaded);
    }

    /**
     * @param  array<int, WaitlistData>  $entries
     * @return array<int, WaitlistData>
     */
    private function applyOverrides(array $entries): array
    {
        foreach ($entries as $entry) {
            if (isset($this->overrides[$entry->uuid])) {
                $entry->status = $this->overrides[$entry->uuid];
            }
        }

        return $entries;
    }

    public function usingFallback(): bool
    {
        $this->source();

        return $this->fallbackUsed;
    }

    /** @return array<int, WaitlistData> */
    #[Computed]
    public function filtered(): array
    {
        $status = $this->statusFilter;

        return array_values(array_filter($this->source(), function (WaitlistData $w) use ($status) {
            // When the API already filtered by status the client filter is a no-op;
            // for fallback data (and optimistic overrides) we still honour it here.
            return $status === 'all' || $w->status === $status;
        }));
    }

    #[Computed]
    public function total(): int
    {
        return count($this->filtered());
    }

    /** @return array{waiting:int, notified:int, booked:int, total:int} */
    #[Computed]
    public function kpis(): array
    {
        $all = $this->source();
        $count = fn (string $s) => count(array_filter($all, fn (WaitlistData $w) => $w->status === $s));

        return [
            'waiting' => $count('waiting'),
            'notified' => $count('notified'),
            'booked' => $count('booked'),
            'total' => count($all),
        ];
    }

    public function notify(string $uuid): void
    {
        $entry = collect($this->source())->firstWhere('uuid', $uuid);
        if (! $entry || $entry->status !== 'waiting') {
            return;
        }

        $this->overrides[$uuid] = 'notified';

        if (! $this->usingFallback()) {
            $this->waqty(fn () => app(WaitlistService::class)->notify($uuid) ?? true, __('waqty.genericError'));
        }

        $this->dispatch('notify', type: 'success', message: __('waitlist.toastNotified'));
        unset($this->filtered, $this->total, $this->kpis);
    }

    public function confirmRemove(string $uuid): void
    {
        $this->deletingUuid = $uuid;
        $this->showDelete = true;
    }

    public function remove(): void
    {
        if (! $this->deletingUuid) {
            return;
        }

        $uuid = $this->deletingUuid;
        $this->overrides[$uuid] = 'cancelled';

        if (! $this->usingFallback()) {
            $this->waqty(fn () => app(WaitlistService::class)->remove($uuid) ?? true, __('waqty.genericError'));
        }

        $this->showDelete = false;
        $this->deletingUuid = null;

        $this->dispatch('notify', type: 'success', message: __('waitlist.toastRemoved'));
        unset($this->filtered, $this->total, $this->kpis);
    }

    public function render()
    {
        return view('livewire.bookings.waitlist');
    }

    /** Sample waitlist for graceful degradation when the API is unreachable. */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'WL1', 'customer' => ['name' => 'فاطمة رشاد', 'phone' => '01011112222'], 'service' => ['name' => 'صبغة شعر'], 'branch_uuid' => 'B1', 'preferred_date' => '2026-07-05', 'preferred_time' => '11:00:00', 'status' => 'waiting', 'position' => 1, 'created_at' => '2026-07-01T09:00:00Z'],
            ['uuid' => 'WL2', 'customer' => ['name' => 'عمر خالد', 'phone' => '01033334444'], 'service' => ['name' => 'قصّة شعر كلاسيك'], 'branch_uuid' => 'B1', 'preferred_date' => '2026-07-05', 'preferred_time' => '13:30:00', 'status' => 'waiting', 'position' => 2, 'created_at' => '2026-07-01T10:15:00Z'],
            ['uuid' => 'WL3', 'customer' => ['name' => 'ليلى حسن', 'phone' => '01055556666'], 'service' => ['name' => 'مانيكير'], 'branch_uuid' => 'B1', 'preferred_date' => '2026-07-06', 'preferred_time' => null, 'status' => 'notified', 'position' => 3, 'created_at' => '2026-06-30T14:00:00Z'],
            ['uuid' => 'WL4', 'customer' => ['name' => 'يوسف عادل', 'phone' => '01077778888'], 'service' => ['name' => 'تهذيب اللحية'], 'branch_uuid' => 'B1', 'preferred_date' => '2026-07-06', 'preferred_time' => '16:00:00', 'status' => 'booked', 'position' => 4, 'created_at' => '2026-06-29T11:30:00Z'],
            ['uuid' => 'WL5', 'customer' => ['name' => 'نور صلاح', 'phone' => '01099990000'], 'service' => ['name' => 'جلسة عناية بالبشرة'], 'branch_uuid' => 'B1', 'preferred_date' => '2026-07-07', 'preferred_time' => '10:30:00', 'status' => 'waiting', 'position' => 5, 'created_at' => '2026-07-02T08:45:00Z'],
            ['uuid' => 'WL6', 'customer' => ['name' => 'كريم فؤاد'], 'service' => ['name' => 'مساج الأنسجة العميقة'], 'branch_uuid' => 'B1', 'preferred_date' => '2026-07-08', 'preferred_time' => '18:00:00', 'status' => 'cancelled', 'position' => 6, 'created_at' => '2026-06-28T16:20:00Z'],
        ];
    }
}
