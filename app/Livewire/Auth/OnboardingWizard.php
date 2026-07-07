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
 * Provider onboarding wizard (port of the /onboarding flow):
 * 1 account details -> 2 verify OTP -> 3 business + branch -> 4 services.
 * Mirrors ForgotPassword: OTP steps advance even when the API is unreachable
 * so the flow is fully walkable without a backend. Finishing seeds a provider
 * session (new-workspace flag) and lands on the dashboard.
 */
#[Layout('components.layouts.guest')]
#[Title('Create your workspace — Waqty')]
class OnboardingWizard extends Component
{
    public int $step = 1;

    // Step 1 — account
    public string $fullName = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public bool $acceptedTerms = false;

    // Step 2 — OTP
    public string $otp = '';

    // Step 3 — business
    public string $businessType = 'salon';

    public string $businessName = '';

    public string $governorate = '';

    public string $city = '';

    public string $address = '';

    public string $branchEmail = '';

    public string $branchPassword = '';

    // Step 4 — services
    /** @var array<int, string> */
    public array $selectedServices = [];

    public string $customService = '';

    public function submitAccount(AuthService $auth): void
    {
        $this->validate([
            'fullName' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email'],
            'phone' => ['required', function ($attr, $value, $fail) {
                if (! EgyptPhone::isValid($value)) {
                    $fail(__('onboarding.toastPhoneInvalid'));
                }
            }],
            'password' => ['required', 'min:6'],
            'acceptedTerms' => ['accepted'],
        ], [
            'fullName.required' => __('onboarding.toastNameReq'),
            'email.required' => __('onboarding.toastEmailReq'),
            'email.email' => __('onboarding.toastEmailInvalid'),
            'phone.required' => __('onboarding.toastPhoneReq'),
            'password.min' => __('onboarding.toastPasswordShort'),
            'acceptedTerms.accepted' => __('onboarding.toastAcceptTerms'),
        ]);

        try {
            $auth->sendOtp(trim($this->email));
        } catch (WaqtyApiException) {
            // Network/other — advance anyway (demo parity).
        }

        $this->reset('otp');
        $this->step = 2;
    }

    public function verifyOtp(AuthService $auth): void
    {
        $this->validate(['otp' => ['required', 'digits:6']]);

        try {
            $auth->verifyOtp(trim($this->email), $this->otp);
        } catch (WaqtyApiException) {
            // Network/other — accept and continue (demo).
        }

        $this->step = 3;
    }

    public function selectBusinessType(string $type): void
    {
        if (in_array($type, ['clinic', 'salon', 'barber'], true)) {
            $this->businessType = $type;
        }
    }

    public function submitBusiness(): void
    {
        $this->validate([
            'businessName' => ['required', 'string', 'max:120'],
            'governorate' => ['required', 'string'],
            'city' => ['required', 'string'],
            'branchEmail' => ['required', 'email'],
            'branchPassword' => ['required', 'min:6'],
        ], [
            'branchPassword.min' => __('onboarding.toastPasswordShort'),
        ]);

        // Preselect the suggested services for the chosen type.
        $this->selectedServices = $this->suggestedServices();
        $this->step = 4;
    }

    public function toggleService(string $name): void
    {
        if (in_array($name, $this->selectedServices, true)) {
            $this->selectedServices = array_values(array_filter($this->selectedServices, fn ($s) => $s !== $name));
        } else {
            $this->selectedServices[] = $name;
        }
    }

    public function addCustomService(): void
    {
        $name = trim($this->customService);
        if ($name !== '' && ! in_array($name, $this->selectedServices, true)) {
            $this->selectedServices[] = $name;
        }
        $this->customService = '';
    }

    public function finish(): void
    {
        if ($this->selectedServices === []) {
            $this->dispatch('notify', type: 'warning', message: __('onboarding.servicesHint'));

            return;
        }

        // Seed a provider session so the authenticated app renders (UI clone —
        // there is no real signup endpoint). Flag it as a fresh workspace.
        session([
            config('waqty.session.provider_token') => 'onboarding-token',
            config('waqty.session.provider_profile') => [
                'name' => trim($this->fullName),
                'email' => trim($this->email),
                'role' => 'admin',
                'business_type' => $this->businessType,
                'category' => ['name' => ucfirst($this->businessType)],
                'branches' => [['name' => trim($this->businessName)]],
            ],
            'waqty.new_workspace' => true,
        ]);
        session()->regenerate();

        $this->redirectRoute('dashboard', navigate: true);
    }

    public function back(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    /** Cities for the selected governorate. @return array<int, string> */
    public function cities(): array
    {
        return $this->governorates()[$this->governorate] ?? [];
    }

    public function updatedGovernorate(): void
    {
        $this->city = '';
    }

    /** Suggested service names for the chosen business type. @return array<int, string> */
    public function suggestedServices(): array
    {
        return match ($this->businessType) {
            'clinic' => ['استشارة عامة', 'زيارة متابعة', 'تنظيف الأسنان', 'فحص الجلد', 'تحليل مخبري'],
            'barber' => ['قصّة شعر', 'تهذيب اللحية', 'حلاقة', 'غسيل شعر', 'قصّة شعر للأطفال'],
            default => ['قصّ وتصفيف الشعر', 'صبغة شعر', 'مانيكير', 'باديكير', 'عناية بالبشرة', 'مساج'],
        };
    }

    public function render()
    {
        return view('livewire.auth.onboarding-wizard');
    }

    /** Egyptian governorate → cities (trimmed sample). @return array<string, array<int, string>> */
    public function governorates(): array
    {
        return [
            'Cairo' => ['Nasr City', 'Maadi', 'Heliopolis', 'New Cairo', 'Downtown'],
            'Giza' => ['Dokki', 'Mohandessin', '6th of October', 'Sheikh Zayed', 'Haram'],
            'Alexandria' => ['Smouha', 'Sidi Gaber', 'Miami', 'Gleem', 'Roushdy'],
        ];
    }
}
