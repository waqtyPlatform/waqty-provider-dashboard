<?php

declare(strict_types=1);

namespace App\Livewire\Returns;

use App\Services\Waqty\ReturnService;
use App\Support\Concerns\HandlesWaqtyErrors;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Cancel-down-payment refund wizard (/returns/cancel-down-payment).
 *
 * Three walkable steps: 1) pick a booking that carries a down payment,
 * 2) choose a cancellation reason + optional notes (with the amount to be
 * returned surfaced), 3) confirm the summary and submit. Mirrors the
 * onboarding wizard shape (int $step, next()/back(), per-step validation) and,
 * like the rest of the clone, still lands on the done panel when the API is
 * unreachable so the flow is fully demoable without a backend.
 */
#[Layout('components.layouts.app')]
#[Title('Cancel down payment — Waqty')]
class CancelDownPayment extends Component
{
    use HandlesWaqtyErrors;

    public int $step = 1;

    // Step 1 — the chosen booking (by uuid).
    public string $bookingUuid = '';

    // Step 2 — reason + notes.
    public string $reason = '';

    public string $notes = '';

    // Final state.
    public bool $done = false;

    public function selectBooking(string $uuid): void
    {
        $this->bookingUuid = $uuid;
    }

    public function next(): void
    {
        $this->validateStep();

        if ($this->step < 3) {
            $this->step++;
        }
    }

    public function back(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function submit(): void
    {
        // Re-assert the whole wizard before committing.
        $this->validate($this->rules(), $this->messages());

        $booking = $this->selectedBooking();
        if ($booking === null) {
            return;
        }

        // Route through the shared handler: 422 -> error bag, 401 -> re-login.
        $this->waqty(function () use ($booking) {
            app(ReturnService::class)->create([
                'type' => 'cancel_down_payment',
                'booking_uuid' => $booking['uuid'],
                'amount' => $booking['downPayment'],
                'reason' => $this->reasons()[$this->reason] ?? $this->reason,
                'notes' => trim($this->notes),
            ]);

            return true;
        }, __('returns.cancelDownPayment.submitFailed'));

        // UI-only clone: land on the done panel even when the API is down.
        $this->done = true;
        $this->dispatch('notify', type: 'success', message: __('returns.cancelDownPayment.successToast'));
    }

    public function startAnother(): void
    {
        $this->reset(['step', 'bookingUuid', 'reason', 'notes', 'done']);
        $this->resetValidation();
    }

    /** The currently selected booking row, if any. @return array<string, mixed>|null */
    public function selectedBooking(): ?array
    {
        foreach ($this->bookings() as $booking) {
            if ($booking['uuid'] === $this->bookingUuid) {
                return $booking;
            }
        }

        return null;
    }

    /** Arabic sample bookings that carry a down payment. @return array<int, array<string, mixed>> */
    public function bookings(): array
    {
        return [
            ['uuid' => 'BK-3182', 'client' => 'فاطمة رشاد', 'service' => 'صبغة شعر', 'date' => '2026-07-08', 'downPayment' => 25000],
            ['uuid' => 'BK-3175', 'client' => 'منى عبد الله', 'service' => 'باقة عناية بالبشرة', 'date' => '2026-07-10', 'downPayment' => 40000],
            ['uuid' => 'BK-3169', 'client' => 'هبة الشناوي', 'service' => 'مكياج عروس', 'date' => '2026-07-12', 'downPayment' => 60000],
            ['uuid' => 'BK-3160', 'client' => 'ريم فؤاد', 'service' => 'باديكير ومانيكير', 'date' => '2026-07-06', 'downPayment' => 15000],
        ];
    }

    /** Cancellation reason options: key => localised label. @return array<string, string> */
    public function reasons(): array
    {
        return [
            'client_request' => __('returns.cancelDownPayment.reasonClientRequest'),
            'schedule_conflict' => __('returns.cancelDownPayment.reasonScheduleConflict'),
            'duplicate_booking' => __('returns.cancelDownPayment.reasonDuplicate'),
            'service_unavailable' => __('returns.cancelDownPayment.reasonUnavailable'),
            'other' => __('returns.cancelDownPayment.reasonOther'),
        ];
    }

    public function render()
    {
        return view('livewire.returns.cancel-down-payment');
    }

    private function validateStep(): void
    {
        $rules = $this->rules();
        $messages = $this->messages();

        $only = match ($this->step) {
            1 => ['bookingUuid'],
            2 => ['reason', 'notes'],
            default => array_keys($rules),
        };

        $this->validate(array_intersect_key($rules, array_flip($only)), $messages);
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(): array
    {
        return [
            'bookingUuid' => ['required', Rule::in(array_column($this->bookings(), 'uuid'))],
            'reason' => ['required', Rule::in(array_keys($this->reasons()))],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'bookingUuid.required' => __('returns.cancelDownPayment.errBooking'),
            'bookingUuid.in' => __('returns.cancelDownPayment.errBooking'),
            'reason.required' => __('returns.cancelDownPayment.errReason'),
            'reason.in' => __('returns.cancelDownPayment.errReason'),
            'notes.max' => __('returns.cancelDownPayment.errNotes'),
        ];
    }
}
