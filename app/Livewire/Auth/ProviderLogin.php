<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Services\Waqty\AuthService;
use App\Services\Waqty\WaqtyApiException;
use App\Support\EgyptPhone;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Sign In — Waqty')]
class ProviderLogin extends Component
{
    public string $identifier = '';

    public string $password = '';

    public bool $showPassword = false;

    public function mount(): void
    {
        if (filled(session(config('waqty.session.provider_token')))) {
            $this->redirectRoute('dashboard', navigate: true);
        }
    }

    public function login(AuthService $auth): void
    {
        $this->validate([
            'identifier' => ['required', function ($attribute, $value, $fail) {
                if (! filter_var($value, FILTER_VALIDATE_EMAIL) && ! EgyptPhone::isValid($value)) {
                    $fail(__('waqty.invalidIdentifier'));
                }
            }],
            'password' => ['required', 'min:6'],
        ], [
            'identifier.required' => __('auth.errorEmpty'),
            'password.required' => __('auth.errorPasswordEmpty'),
            'password.min' => __('auth.errorPasswordMin'),
        ]);

        try {
            $auth->authenticateProvider(trim($this->identifier), $this->password);
        } catch (WaqtyApiException $e) {
            if ($e->isValidation()) {
                foreach ($e->validationErrors as $field => $messages) {
                    $this->addError($field, is_array($messages) ? ($messages[0] ?? '') : (string) $messages);
                }

                return;
            }

            $this->addError('identifier', $e->status === 401 ? __('auth.errorLogin') : __('waqty.genericError'));

            return;
        }

        session()->regenerate();

        $redirect = request()->query('redirect');
        $target = is_string($redirect) && $redirect !== '' ? '/'.ltrim($redirect, '/') : route('dashboard');

        $this->redirect($target, navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.provider-login');
    }
}
