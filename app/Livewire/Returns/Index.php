<?php

declare(strict_types=1);

namespace App\Livewire\Returns;

use App\Data\Waqty\ReturnData;
use App\Services\Waqty\ReturnService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Returns — Waqty')]
class Index extends Component
{
    use HandlesWaqtyErrors;

    public string $typeFilter = 'all';

    public string $statusFilter = 'all';

    // Reject modal
    public bool $showReject = false;

    public ?string $rejectingUuid = null;

    public string $rejectReason = '';

    /** Optimistic status after approve/reject. @var array<string, string> */
    public array $overrides = [];

    /** @var array<int, ReturnData>|null */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    /** @return array<int, ReturnData> */
    private function source(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $this->loaded = app(ReturnService::class)->returns(['per_page' => 100]);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = array_map(fn ($a) => ReturnData::from($a), $this->fallbackData());
        }

        foreach ($this->loaded as $r) {
            if (isset($this->overrides[$r->uuid])) {
                $r->status = $this->overrides[$r->uuid];
            }
        }

        return $this->loaded;
    }

    public function usingFallback(): bool
    {
        $this->source();

        return $this->fallbackUsed;
    }

    /** @return array<int, ReturnData> */
    #[Computed]
    public function filtered(): array
    {
        $type = $this->typeFilter;
        $status = $this->statusFilter;

        return array_values(array_filter($this->source(), function (ReturnData $r) use ($type, $status) {
            return ($type === 'all' || $r->type === $type) && ($status === 'all' || $r->status === $status);
        }));
    }

    /** @return array{pending:int, approved:int, rejected:int, amount:int} */
    #[Computed]
    public function kpis(): array
    {
        $all = $this->source();
        $count = fn (string $s) => count(array_filter($all, fn (ReturnData $r) => $r->status === $s));

        return [
            'pending' => $count('pending'),
            'approved' => $count('approved'),
            'rejected' => $count('rejected'),
            'amount' => array_sum(array_map(fn (ReturnData $r) => $r->status === 'approved' ? $r->amount : 0, $all)),
        ];
    }

    public function approve(string $uuid): void
    {
        $this->overrides[$uuid] = 'approved';

        if (! $this->usingFallback()) {
            $this->waqty(fn () => app(ReturnService::class)->approve($uuid) ?? true, __('waqty.genericError'));
        }

        unset($this->filtered, $this->kpis);
        $this->dispatch('notify', type: 'success', message: __('common.saved'));
    }

    public function openReject(string $uuid): void
    {
        $this->rejectingUuid = $uuid;
        $this->rejectReason = '';
        $this->resetValidation();
        $this->showReject = true;
    }

    public function submitReject(): void
    {
        $this->validate(['rejectReason' => ['required', 'string', 'max:300']], [
            'rejectReason.required' => __('ret.rejectReason'),
        ]);

        $uuid = $this->rejectingUuid;
        $reason = trim($this->rejectReason);
        $this->overrides[$uuid] = 'rejected';

        if (! $this->usingFallback()) {
            $this->waqty(fn () => app(ReturnService::class)->reject($uuid, $reason) ?? true, __('waqty.genericError'));
        }

        $this->showReject = false;
        $this->rejectingUuid = null;
        unset($this->filtered, $this->kpis);
        $this->dispatch('notify', type: 'success', message: __('common.saved'));
    }

    public function render()
    {
        return view('livewire.returns.index');
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'RT1', 'type' => 'cash_refund', 'customer' => ['name' => 'يوسف علي'], 'amount' => 15000, 'reason' => 'الخدمة لم تكن كما هو متوقع', 'status' => 'pending', 'created_at' => '2026-07-03 14:00:00'],
            ['uuid' => 'RT2', 'type' => 'cancel_down_payment', 'customer' => ['name' => 'سلمى إبراهيم'], 'amount' => 25000, 'reason' => 'تم إلغاء الحجز من قبل العميل', 'status' => 'pending', 'created_at' => '2026-07-02 11:30:00'],
            ['uuid' => 'RT3', 'type' => 'cash_refund', 'customer' => ['name' => 'عمر خالد'], 'amount' => 8000, 'reason' => 'خصم مزدوج', 'status' => 'approved', 'created_at' => '2026-07-01 16:20:00'],
            ['uuid' => 'RT4', 'type' => 'petty_cash_refund', 'customer' => null, 'amount' => 12000, 'reason' => 'إرجاع مستلزمات غير مستخدمة', 'status' => 'approved', 'created_at' => '2026-06-30 09:15:00'],
            ['uuid' => 'RT5', 'type' => 'cash_refund', 'customer' => ['name' => 'كريم مصطفى'], 'amount' => 30000, 'reason' => 'طلب استرداد دون سبب وجيه', 'status' => 'rejected', 'created_at' => '2026-06-28 13:45:00'],
        ];
    }
}
