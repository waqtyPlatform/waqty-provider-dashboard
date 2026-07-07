<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Services\Waqty\AuthService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\EgyptPhone;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Provider password reset (port of AuthContext forgot-password state machine):
 * 1 request OTP -> 2 verify OTP -> 3 set new password -> 4 success.
 * When the API is unreachable the steps still advance (demo parity with the
 * source's mock fallback) so the flow is fully walkable without a backend.
 */
#[Layout('components.layouts.guest')]
#[Title('Reset Password — Waqty')]
class ForgotPassword extends Component
{
    public int $step = 1;

    public string $identifier = '';

    public string $otp = '';

    public string $newPassword = '';

    public string $confirmPassword = '';

    public function sendCode(AuthService $auth): void
    {
        $this->validate([
            'identifier' => ['required', function ($attr, $value, $fail) {
                if (! filter_var($value, FILTER_VALIDATE_EMAIL) && ! EgyptPhone::isValid($value)) {
                    $fail(__('waqty.invalidIdentifier'));
                }
            }],
        ], ['identifier.required' => __('auth.errorEmpty')]);

        try {
            $auth->sendOtp(trim($this->identifier));
        } catch (WaqtyApiException $e) {
            if ($e->status === 429) {
                $this->addError('identifier', __('auth.errorRequest'));

                return;
            }
            // Network/other — advance anyway so the demo flow continues.
        }

        $this->reset('otp');
        $this->step = 2;
    }

    public function verifyCode(AuthService $auth): void
    {
        $this->validate(['otp' => ['required', 'digits:6']]);

        try {
            if (! $auth->verifyOtp(trim($this->identifier), $this->otp)) {
                $this->addError('otp', __('auth.errorVerify'));

                return;
            }
        } catch (WaqtyApiException) {
            // Network/other — accept and continue (demo).
        }

        $this->step = 3;
    }

    public function resetPassword(AuthService $auth): void
    {
        $this->validate([
            'newPassword' => ['required', 'min:8'],
            'confirmPassword' => ['required', 'same:newPassword'],
        ]);

        try {
            $auth->resetPassword(trim($this->identifier), $this->otp, $this->newPassword);
        } catch (WaqtyApiException $e) {
            if ($e->status === 429) {
                $this->addError('newPassword', __('auth.errorReset'));

                return;
            }
            // Network/other — treat as success (demo).
        }

        $this->step = 4;
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
