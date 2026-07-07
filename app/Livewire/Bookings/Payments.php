<?php

declare(strict_types=1);

namespace App\Livewire\Bookings;

use App\Data\Waqty\PaymentData;
use App\Services\Waqty\PaymentService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\Concerns\HandlesWaqtyErrors;
use App\Support\Money;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Payments — Waqty')]
class Payments extends Component
{
    use HandlesWaqtyErrors;

    public string $search = '';

    public string $methodFilter = 'all';

    public string $statusFilter = 'all';

    public int $currentPage = 1;

    public int $perPage = 10;

    // Record / edit slide-over
    public bool $showForm = false;

    public ?string $editingUuid = null;

    public string $form_booking_uuid = '';

    public string $form_amount = '';

    public string $form_payment_method = 'cash';

    public string $form_status = 'pending';

    public string $form_transaction_id = '';

    public string $form_notes = '';

    // Delete confirmation
    public bool $showDelete = false;

    public ?string $deletingUuid = null;

    /** @var array<int, PaymentData>|null per-request memo */
    private ?array $loaded = null;

    private bool $fallbackUsed = false;

    public function updatedSearch(): void
    {
        $this->currentPage = 1;
    }

    public function updatedMethodFilter(): void
    {
        $this->currentPage = 1;
    }

    public function updatedStatusFilter(): void
    {
        $this->currentPage = 1;
    }

    /** @return array<int, PaymentData> */
    private function source(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            // Fetch the full set unfiltered so the KPI band stays global; the
            // method/status filters are applied client-side in filtered().
            $this->loaded = app(PaymentService::class)->list(['per_page' => 100]);
        } catch (WaqtyApiException) {
            $this->fallbackUsed = true;
            $this->loaded = array_map(fn ($a) => PaymentData::from($a), $this->fallbackData());
        }

        return $this->loaded;
    }

    public function usingFallback(): bool
    {
        $this->source();

        return $this->fallbackUsed;
    }

    /** @return array<int, PaymentData> */
    #[Computed]
    public function filtered(): array
    {
        $search = trim(mb_strtolower($this->search));
        $method = $this->methodFilter;
        $status = $this->statusFilter;

        return array_values(array_filter($this->source(), function (PaymentData $p) use ($search, $method, $status) {
            $matchesSearch = $search === ''
                || str_contains(mb_strtolower($p->serviceName()), $search)
                || str_contains(mb_strtolower((string) $p->transaction_id), $search);

            $matchesMethod = $method === 'all' || $p->payment_method === $method;
            $matchesStatus = $status === 'all' || $p->status === $status;

            return $matchesSearch && $matchesMethod && $matchesStatus;
        }));
    }

    /** @return array<int, PaymentData> */
    #[Computed]
    public function paginated(): array
    {
        return array_slice($this->filtered(), ($this->currentPage - 1) * $this->perPage, $this->perPage);
    }

    #[Computed]
    public function total(): int
    {
        return count($this->filtered());
    }

    /** @return array{collected:int, pending:int, refunded:int, records:int} */
    #[Computed]
    public function kpis(): array
    {
        $all = $this->source();

        return [
            'collected' => array_sum(array_map(
                fn (PaymentData $p) => $p->status === 'completed' ? (int) $p->amount : 0,
                $all,
            )),
            'pending' => count(array_filter($all, fn (PaymentData $p) => $p->status === 'pending')),
            'refunded' => array_sum(array_map(
                fn (PaymentData $p) => $p->status === 'refunded' ? (int) $p->amount : 0,
                $all,
            )),
            'records' => count($all),
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['editingUuid', 'form_booking_uuid', 'form_amount', 'form_transaction_id', 'form_notes']);
        $this->form_payment_method = 'cash';
        $this->form_status = 'pending';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(string $uuid): void
    {
        $payment = collect($this->source())->firstWhere('uuid', $uuid);
        if (! $payment) {
            return;
        }

        $this->editingUuid = $uuid;
        $this->form_booking_uuid = (string) $payment->booking_uuid;
        $this->form_amount = $payment->amount !== null ? (string) Money::fromMinor((int) $payment->amount) : '';
        $this->form_payment_method = $payment->payment_method;
        $this->form_status = $payment->status;
        $this->form_transaction_id = (string) $payment->transaction_id;
        $this->form_notes = (string) $payment->notes;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_booking_uuid' => ['required', 'string'],
            'form_amount' => ['required', 'numeric', 'min:0'],
            'form_payment_method' => ['required', 'in:cash,paymob'],
            'form_status' => ['required', 'in:pending,completed,failed,refunded'],
            'form_transaction_id' => ['nullable', 'string', 'max:100'],
            'form_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $payload = [
            'amount' => Money::toMinor((float) $this->form_amount),
            'payment_method' => $this->form_payment_method,
            'status' => $this->form_status,
            'transaction_id' => trim($this->form_transaction_id) ?: null,
            'notes' => trim($this->form_notes) ?: null,
        ];

        $service = app(PaymentService::class);
        $bookingUuid = trim($this->form_booking_uuid);

        $result = $this->waqty(function () use ($service, $payload, $bookingUuid) {
            $this->editingUuid
                ? $service->update($this->editingUuid, $payload)
                : $service->create($bookingUuid, $payload);

            return true;
        }, __('waqty.genericError'));

        if ($result) {
            $this->showForm = false;
            $this->dispatch('notify', type: 'success', message: $this->editingUuid ? __('payments.toastUpdated') : __('payments.toastRecorded'));
            $this->editingUuid = null;
            unset($this->filtered, $this->paginated, $this->total, $this->kpis);
        }
    }

    public function complete(string $uuid): void
    {
        $this->setStatus($uuid, 'completed');
    }

    public function refund(string $uuid): void
    {
        $this->setStatus($uuid, 'refunded');
    }

    private function setStatus(string $uuid, string $status): void
    {
        $service = app(PaymentService::class);

        $result = $this->waqty(function () use ($service, $uuid, $status) {
            $service->update($uuid, ['status' => $status]);

            return true;
        }, __('waqty.genericError'));

        if ($result) {
            $this->dispatch('notify', type: 'success', message: __('payments.toastUpdated'));
            unset($this->filtered, $this->paginated, $this->total, $this->kpis);
        }
    }

    public function confirmDelete(string $uuid): void
    {
        $this->deletingUuid = $uuid;
        $this->showDelete = true;
    }

    public function deletePayment(): void
    {
        if (! $this->deletingUuid) {
            return;
        }

        $service = app(PaymentService::class);
        $uuid = $this->deletingUuid;

        $result = $this->waqty(fn () => $service->delete($uuid) ?? true, __('waqty.genericError'));

        $this->showDelete = false;
        $this->deletingUuid = null;

        if ($result) {
            $this->dispatch('notify', type: 'success', message: __('common.deleted'));
            unset($this->filtered, $this->paginated, $this->total, $this->kpis);
        }
    }

    public function render()
    {
        return view('livewire.bookings.payments');
    }

    /**
     * Sample data mirroring the source fallback payments for graceful degradation.
     * Amounts are integer minor units (100 = 1 EGP).
     *
     * @return array<int, array<string, mixed>>
     */
    private function fallbackData(): array
    {
        return [
            ['uuid' => 'PMT001', 'booking_uuid' => 'BK1001', 'booking' => ['uuid' => 'BK1001', 'booking_date' => '2026-06-30', 'service' => ['name' => 'صبغة شعر']], 'payment_method' => 'paymob', 'amount' => 45000, 'status' => 'completed', 'transaction_id' => 'PMB-8842013', 'notes' => null, 'created_at' => '2026-06-30'],
            ['uuid' => 'PMT002', 'booking_uuid' => 'BK1002', 'booking' => ['uuid' => 'BK1002', 'booking_date' => '2026-06-29', 'service' => ['name' => 'قصّة شعر كلاسيك']], 'payment_method' => 'cash', 'amount' => 15000, 'status' => 'completed', 'transaction_id' => null, 'notes' => 'تم الدفع عند الكاونتر', 'created_at' => '2026-06-29'],
            ['uuid' => 'PMT003', 'booking_uuid' => 'BK1003', 'booking' => ['uuid' => 'BK1003', 'booking_date' => '2026-07-01', 'service' => ['name' => 'تهذيب اللحية']], 'payment_method' => 'paymob', 'amount' => 8000, 'status' => 'pending', 'transaction_id' => 'PMB-8842140', 'notes' => null, 'created_at' => '2026-07-01'],
            ['uuid' => 'PMT004', 'booking_uuid' => 'BK1004', 'booking' => ['uuid' => 'BK1004', 'booking_date' => '2026-06-28', 'service' => ['name' => 'هايلايت كامل']], 'payment_method' => 'paymob', 'amount' => 92000, 'status' => 'refunded', 'transaction_id' => 'PMB-8841007', 'notes' => 'ألغى العميل الحجز', 'created_at' => '2026-06-28'],
            ['uuid' => 'PMT005', 'booking_uuid' => 'BK1005', 'booking' => ['uuid' => 'BK1005', 'booking_date' => '2026-07-02', 'service' => ['name' => 'مانيكير']], 'payment_method' => 'cash', 'amount' => 12000, 'status' => 'completed', 'transaction_id' => null, 'notes' => null, 'created_at' => '2026-07-02'],
            ['uuid' => 'PMT006', 'booking_uuid' => 'BK1006', 'booking' => ['uuid' => 'BK1006', 'booking_date' => '2026-07-02', 'service' => ['name' => 'مساج الأنسجة العميقة']], 'payment_method' => 'paymob', 'amount' => 55000, 'status' => 'failed', 'transaction_id' => 'PMB-8842301', 'notes' => 'تم رفض البطاقة', 'created_at' => '2026-07-02'],
        ];
    }
}
