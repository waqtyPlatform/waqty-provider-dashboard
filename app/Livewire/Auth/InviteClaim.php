<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Services\Waqty\AuthService;
use App\Services\Waqty\WaqtyApiException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Staff invite claim (mock — no real invite lookup). The signed {token} in the
 * URL identifies the invite; here it is accepted as-is so the flow is walkable
 * without a backend. 1 verify invited phone -> 2 enter OTP + full name -> done
 * panel that links into the employee-portal login. OTP send/verify are
 * best-effort: the steps advance even when the API is unreachable (demo parity
 * with the other guest auth flows).
 */
#[Layout('components.layouts.guest')]
#[Title('Join Workspace — Waqty')]
class InviteClaim extends Component
{
    public string $token = '';

    public int $step = 1;

    public string $phone = '';

    public string $otp = '';

    public string $fullName = '';

    public function mount(string $token): void
    {
        $this->token = $token;
    }

    public function sendCode(AuthService $auth): void
    {
        $this->validate([
            'phone' => ['required', 'regex:/^01[0125][0-9]{8}$/'],
        ], [
            'phone.required' => __('invite.toastPhoneReq'),
            'phone.regex' => __('onboarding.toastPhoneInvalid'),
        ]);

        try {
            $auth->sendOtp($this->phone);
        } catch (WaqtyApiException) {
            // Best-effort — advance anyway (mock has no invite backend).
        }

        $this->reset('otp');
        $this->step = 2;
        $this->dispatch('notify', type: 'success', message: __('invite.toastCodeSent'));
    }

    public function verify(AuthService $auth): void
    {
        $this->validate([
            'otp' => ['required', 'digits:6'],
            'fullName' => ['required', 'string', 'max:100'],
        ], [
            'otp.required' => __('onboarding.toastCodeInvalid'),
            'otp.digits' => __('onboarding.toastCodeInvalid'),
            'fullName.required' => __('invite.toastNameReq'),
        ]);

        try {
            $auth->verifyOtp($this->phone, $this->otp);
        } catch (WaqtyApiException) {
            // Best-effort — accept and continue (demo).
        }

        $this->step = 3;
        $this->dispatch('notify', type: 'success', message: __('invite.toastSetupComplete'));
    }

    public function back(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function render()
    {
        return view('livewire.auth.invite-claim');
    }
}
