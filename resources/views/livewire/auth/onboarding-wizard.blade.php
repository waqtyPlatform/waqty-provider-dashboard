@php
    $inputClass = 'w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20';
    $btnClass = 'mt-5 flex w-full items-center justify-center gap-2 rounded-lg bg-primary-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500/40 disabled:opacity-60';
@endphp

<div class="w-full max-w-xl">
    <div class="mb-6 flex flex-col items-center text-center">
        <div class="mb-4 grid size-14 place-items-center rounded-2xl bg-primary-500 text-2xl font-bold text-white shadow-primary">و</div>
        <h1 class="text-2xl font-semibold text-fg">
            {{ match ($step) { 1 => __('onboarding.titleCreateAccount'), 2 => __('onboarding.titleVerifyIdentity'), 3 => __('onboarding.titleAboutBusiness'), default => __('onboarding.titleAddServices') } }}
        </h1>
        <p class="mt-1 text-sm text-fg-muted">
            {{ match ($step) { 1 => __('onboarding.descCreateAccount'), 2 => str_replace('{email}', $email, __('onboarding.descVerifyIdentity')), 3 => __('onboarding.descAboutBusiness'), default => __('onboarding.descAddServices') } }}
        </p>
    </div>

    {{-- Step indicator --}}
    <div class="mb-6 flex items-center justify-center gap-2">
        @foreach ([1, 2, 3, 4] as $s)
            <span class="h-1.5 rounded-full transition-all {{ $s === $step ? 'w-8 bg-primary-500' : ($s < $step ? 'w-8 bg-primary-300' : 'w-4 bg-line') }}"></span>
        @endforeach
    </div>

    <div class="rounded-2xl border border-line bg-surface p-6 shadow-md sm:p-8">
        {{-- Step 1: account --}}
        @if ($step === 1)
            <form wire:submit="submitAccount" class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('onboarding.fullName') }}</label>
                    <input type="text" wire:model="fullName" placeholder="{{ __('onboarding.fullNamePlaceholder') }}" class="{{ $inputClass }} @error('fullName') border-error @enderror">
                    @error('fullName') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('onboarding.email') }}</label>
                    <input type="email" wire:model="email" dir="ltr" class="{{ $inputClass }} @error('email') border-error @enderror">
                    @error('email') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('onboarding.phoneNumber') }}</label>
                    <input type="tel" wire:model="phone" dir="ltr" placeholder="01XXXXXXXXX" class="{{ $inputClass }} @error('phone') border-error @enderror">
                    @error('phone') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('onboarding.password') }}</label>
                    <input type="password" wire:model="password" placeholder="{{ __('onboarding.passwordPlaceholder') }}" class="{{ $inputClass }} @error('password') border-error @enderror">
                    @error('password') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                </div>
                <label class="flex items-start gap-2.5 text-sm text-fg-muted">
                    <input type="checkbox" wire:model="acceptedTerms" class="mt-0.5 size-4 rounded border-line text-primary-600 focus:ring-primary-500/30">
                    <span>{{ __('onboarding.agreeTo') }} <span class="text-primary-600">{{ __('onboarding.termsLink') }}</span> {{ __('onboarding.and') }} <span class="text-primary-600">{{ __('onboarding.privacyLink') }}</span></span>
                </label>
                @error('acceptedTerms') <p class="text-xs text-error">{{ $message }}</p> @enderror
                <button type="submit" wire:loading.attr="disabled" wire:target="submitAccount" class="{{ $btnClass }}">
                    <span wire:loading.remove wire:target="submitAccount">{{ __('onboarding.continue') }}</span>
                    <span wire:loading wire:target="submitAccount">{{ __('onboarding.sending') }}</span>
                </button>
                <p class="text-center text-sm text-fg-muted">{{ __('onboarding.alreadyHaveAccount') }} <a href="{{ route('login') }}" class="font-medium text-primary-600">{{ __('onboarding.logIn') }}</a></p>
            </form>
        @endif

        {{-- Step 2: OTP --}}
        @if ($step === 2)
            <form wire:submit="verifyOtp" class="space-y-4">
                <input type="text" wire:model="otp" inputmode="numeric" maxlength="6" dir="ltr" placeholder="______"
                    class="w-full rounded-lg border border-line bg-surface px-3.5 py-3 text-center text-2xl tracking-[0.5em] text-fg focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 @error('otp') border-error @enderror">
                @error('otp') <p class="text-center text-xs text-error">{{ $message }}</p> @enderror
                <p class="rounded-lg bg-surface-2 px-3 py-2 text-center text-xs text-fg-subtle">{{ __('onboarding.demoTip') }}<span class="font-mono font-semibold">123456</span></p>
                <button type="submit" class="{{ $btnClass }}">{{ __('onboarding.verifyContinue') }}</button>
                <button type="button" wire:click="back" class="w-full text-center text-sm text-fg-muted hover:text-fg">{{ __('onboarding.back') }}</button>
            </form>
        @endif

        {{-- Step 3: business --}}
        @if ($step === 3)
            <form wire:submit="submitBusiness" class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('onboarding.businessType') }}</label>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                        @foreach (['clinic' => ['onboarding.bizClinicLabel', 'activity'], 'salon' => ['onboarding.bizSalonLabel', 'sparkles'], 'barber' => ['onboarding.bizBarberLabel', 'scissors']] as $type => $meta)
                            <button type="button" wire:click="selectBusinessType('{{ $type }}')"
                                @class([
                                    'flex flex-col items-center gap-1.5 rounded-xl border p-3 text-center transition',
                                    'border-primary-500 bg-primary-50 text-primary-700 ring-1 ring-primary-500' => $businessType === $type,
                                    'border-line text-fg-muted hover:border-line-strong' => $businessType !== $type,
                                ])>
                                <x-icon :name="$meta[1]" class="size-6" />
                                <span class="text-xs font-medium">{{ __($meta[0]) }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('onboarding.businessName') }}</label>
                    <input type="text" wire:model="businessName" placeholder="{{ __('onboarding.businessNamePlaceholder') }}" class="{{ $inputClass }} @error('businessName') border-error @enderror">
                    @error('businessName') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('onboarding.governorate') }}</label>
                        <select wire:model.live="governorate" class="{{ $inputClass }} @error('governorate') border-error @enderror">
                            <option value="">{{ __('onboarding.selectGovernorate') }}</option>
                            @foreach ($this->governorates() as $gov => $cities)
                                <option value="{{ $gov }}">{{ $gov }}</option>
                            @endforeach
                        </select>
                        @error('governorate') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('onboarding.cityArea') }}</label>
                        <select wire:model="city" class="{{ $inputClass }} @error('city') border-error @enderror">
                            <option value="">{{ __('onboarding.selectCityArea') }}</option>
                            @foreach ($this->cities() as $c)
                                <option value="{{ $c }}">{{ $c }}</option>
                            @endforeach
                        </select>
                        @error('city') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('onboarding.fullAddress') }} <span class="text-xs text-fg-subtle">({{ __('onboarding.optional') }})</span></label>
                    <input type="text" wire:model="address" placeholder="{{ __('onboarding.streetName') }}" class="{{ $inputClass }}">
                </div>
                <div class="rounded-xl border border-line bg-surface-2 p-3.5">
                    <p class="mb-2 text-sm font-medium text-fg">{{ __('onboarding.branchCredentials') }} <span class="text-xs text-error">({{ __('onboarding.required') }})</span></p>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <input type="email" wire:model="branchEmail" dir="ltr" placeholder="{{ __('onboarding.branchEmail') }}" class="{{ $inputClass }} @error('branchEmail') border-error @enderror">
                            @error('branchEmail') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <input type="password" wire:model="branchPassword" placeholder="{{ __('onboarding.branchPassword') }}" class="{{ $inputClass }} @error('branchPassword') border-error @enderror">
                            @error('branchPassword') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="button" wire:click="back" class="rounded-lg border border-line px-4 py-2.5 text-sm font-medium text-fg-muted hover:bg-surface-2">{{ __('onboarding.back') }}</button>
                    <button type="submit" class="flex flex-1 items-center justify-center rounded-lg bg-primary-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-600">{{ __('onboarding.continue') }}</button>
                </div>
            </form>
        @endif

        {{-- Step 4: services --}}
        @if ($step === 4)
            <div class="space-y-4">
                <div>
                    <p class="mb-2 text-sm font-medium text-fg">{{ __('onboarding.suggestedFor') }}{{ __('onboarding.seg'.ucfirst($businessType === 'barber' ? 'Barbershop' : $businessType)) }}</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($this->suggestedServices() as $svc)
                            @php($on = in_array($svc, $selectedServices, true))
                            <button type="button" wire:click="toggleService('{{ $svc }}')"
                                @class([
                                    'flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm transition',
                                    'border-primary-500 bg-primary-50 text-primary-700' => $on,
                                    'border-line text-fg-muted hover:border-line-strong' => ! $on,
                                ])>
                                <x-icon :name="$on ? 'check' : 'plus'" class="size-3.5" />{{ $svc }}
                            </button>
                        @endforeach
                    </div>
                </div>

                @php($custom = array_values(array_diff($selectedServices, $this->suggestedServices())))
                @if ($custom !== [])
                    <div>
                        <p class="mb-2 text-sm font-medium text-fg">{{ __('onboarding.yourCustomServices') }}</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($custom as $svc)
                                <button type="button" wire:click="toggleService('{{ $svc }}')" class="flex items-center gap-1.5 rounded-full border border-primary-500 bg-primary-50 px-3 py-1.5 text-sm text-primary-700">
                                    <x-icon name="x" class="size-3.5" />{{ $svc }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form wire:submit="addCustomService" class="flex items-end gap-2">
                    <div class="flex-1">
                        <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('onboarding.addYourOwn') }}</label>
                        <input type="text" wire:model="customService" placeholder="{{ __('onboarding.customServicePlaceholder') }}" class="{{ $inputClass }}">
                    </div>
                    <button type="submit" class="rounded-lg border border-line px-4 py-2.5 text-sm font-medium text-fg hover:bg-surface-2">{{ __('onboarding.addServiceBtn') }}</button>
                </form>

                <p class="text-xs text-fg-subtle">{{ __('onboarding.servicesHint') }}</p>

                <div class="flex gap-2 pt-2">
                    <button type="button" wire:click="back" class="rounded-lg border border-line px-4 py-2.5 text-sm font-medium text-fg-muted hover:bg-surface-2">{{ __('onboarding.back') }}</button>
                    <button type="button" wire:click="finish" class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-primary-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-600">
                        <x-icon name="check" class="size-4" />{{ __('onboarding.goToDashboard') }}
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
