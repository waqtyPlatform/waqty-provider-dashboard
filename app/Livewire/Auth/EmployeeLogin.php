<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Services\Waqty\AuthService;
use App\Services\Waqty\WaqtyApiException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Employee-portal login (employee token surface). Mirrors ProviderLogin but
 * authenticates via /api/employee/auth/login and lands on the employee portal.
 */
#[Layout('components.layouts.guest')]
#[Title('Employee Portal — Waqty')]
class EmployeeLogin extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $showPassword = false;

    public function mount(): void
    {
        if (filled(session(config('waqty.session.employee_token')))) {
            $this->redirectRoute('employee-portal.dashboard', navigate: true);
        }
    }

    public function login(AuthService $auth): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'min:6'],
        ], [
            'email.required' => __('auth.errorEmpty'),
            'password.required' => __('auth.errorPasswordEmpty'),
            'password.min' => __('auth.errorPasswordMin'),
        ]);

        try {
            $auth->authenticateEmployee(trim($this->email), $this->password);
        } catch (WaqtyApiException $e) {
            if ($e->isValidation()) {
                foreach ($e->validationErrors as $field => $messages) {
                    $this->addError($field, is_array($messages) ? ($messages[0] ?? '') : (string) $messages);
                }

                return;
            }

            $this->addError('email', $e->status === 401 ? __('auth.errorLogin') : __('waqty.genericError'));

            return;
        }

        session()->regenerate();
        $this->redirectRoute('employee-portal.dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.employee-login');
    }
}
