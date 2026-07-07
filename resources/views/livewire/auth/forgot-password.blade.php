@php
    $inputClass = 'w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20';
@endphp

<div class="w-full max-w-md">
    <div class="mb-8 flex flex-col items-center text-center">
        <div class="mb-4 grid size-14 place-items-center rounded-2xl bg-primary-500 text-2xl font-bold text-white shadow-primary">و</div>
        <h1 class="text-2xl font-semibold text-fg">
            {{ match ($step) { 1 => __('auth.forgotPasswordTitle'), 2 => __('auth.verifyIdentity'), 3 => __('auth.resetPasswordTitle'), default => __('auth.passwordResetSuccess') } }}
        </h1>
        <p class="mt-1 text-sm text-fg-muted">
            {{ match ($step) { 1 => __('auth.forgotPasswordDesc'), 2 => __('auth.codeSentTo').' '.$identifier, 3 => __('auth.resetPasswordDesc'), default => __('auth.passwordResetSuccessDesc') } }}
        </p>
    </div>

    {{-- Step indicator --}}
    @if ($step < 4)
        <div class="mb-6 flex items-center justify-center gap-2">
            @foreach ([1, 2, 3] as $s)
                <span class="h-1.5 rounded-full transition-all {{ $s === $step ? 'w-8 bg-primary-500' : ($s < $step ? 'w-8 bg-primary-300' : 'w-4 bg-line') }}"></span>
            @endforeach
        </div>
    @endif

    <div class="rounded-2xl border border-line bg-surface p-6 shadow-md sm:p-8">
        {{-- Step 1: request --}}
        @if ($step === 1)
            <form wire:submit="sendCode">
                <label for="identifier" class="mb-1.5 block text-sm font-medium text-fg">{{ __('auth.lblIdentifier') }}</label>
                <input id="identifier" type="text" wire:model="identifier" dir="ltr" autocomplete="username" placeholder="{{ __('auth.phIdentifier') }}" class="{{ $inputClass }} @error('identifier') border-error @enderror">
                @error('identifier') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                <p class="mt-3 rounded-lg bg-surface-2 px-3 py-2 text-xs text-fg-subtle">{{ __('auth.demoTipRequest') }}</p>
                <button type="submit" wire:loading.attr="disabled" wire:target="sendCode" class="mt-5 flex w-full items-center justify-center gap-2 rounded-lg bg-primary-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500/40 disabled:opacity-60">
                    <span wire:loading.remove wire:target="sendCode">{{ __('auth.btnSendCode') }}</span>
                    <span wire:loading wire:target="sendCode">{{ __('auth.btnSending') }}</span>
                </button>
            </form>
        @endif

        {{-- Step 2: verify --}}
        @if ($step === 2)
            <form wire:submit="verifyCode">
                <label for="otp" class="mb-1.5 block text-sm font-medium text-fg">{{ __('auth.lblOtp') }}</label>
                <input id="otp" type="text" inputmode="numeric" maxlength="6" wire:model="otp" dir="ltr" placeholder="000000" class="{{ $inputClass }} text-center font-mono text-lg tracking-[0.5em] @error('otp') border-error @enderror">
                @error('otp') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                <p class="mt-3 rounded-lg bg-surface-2 px-3 py-2 text-xs text-fg-subtle">{{ __('auth.demoTipVerify') }} <span class="font-mono font-semibold text-fg">123456</span></p>
                <button type="submit" wire:loading.attr="disabled" wire:target="verifyCode" class="mt-5 flex w-full items-center justify-center gap-2 rounded-lg bg-primary-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500/40 disabled:opacity-60">
                    <span wire:loading.remove wire:target="verifyCode">{{ __('auth.btnVerifyCode') }}</span>
                    <span wire:loading wire:target="verifyCode">{{ __('auth.btnVerifying') }}</span>
                </button>
                <button type="button" wire:click="sendCode" class="mt-3 w-full text-center text-xs font-medium text-link hover:underline">{{ __('auth.resendCode') }}</button>
            </form>
        @endif

        {{-- Step 3: reset --}}
        @if ($step === 3)
            <form wire:submit="resetPassword">
                <div class="mb-4">
                    <label for="newPassword" class="mb-1.5 block text-sm font-medium text-fg">{{ __('auth.lblNewPassword') }}</label>
                    <input id="newPassword" type="password" wire:model="newPassword" dir="ltr" placeholder="{{ __('auth.phNewPassword') }}" class="{{ $inputClass }} @error('newPassword') border-error @enderror">
                    @error('newPassword') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="confirmPassword" class="mb-1.5 block text-sm font-medium text-fg">{{ __('auth.lblConfirmPassword') }}</label>
                    <input id="confirmPassword" type="password" wire:model="confirmPassword" dir="ltr" placeholder="{{ __('auth.phConfirmPassword') }}" class="{{ $inputClass }} @error('confirmPassword') border-error @enderror">
                    @error('confirmPassword') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                </div>
                <button type="submit" wire:loading.attr="disabled" wire:target="resetPassword" class="mt-5 flex w-full items-center justify-center gap-2 rounded-lg bg-primary-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500/40 disabled:opacity-60">
                    <span wire:loading.remove wire:target="resetPassword">{{ __('auth.btnResetPassword') }}</span>
                    <span wire:loading wire:target="resetPassword">{{ __('auth.btnResetting') }}</span>
                </button>
            </form>
        @endif

        {{-- Step 4: success --}}
        @if ($step === 4)
            <div class="flex flex-col items-center text-center">
                <div class="mb-4 grid size-14 place-items-center rounded-full bg-success-light text-success"><x-icon name="check-circle-2" class="size-7" /></div>
                <a href="{{ route('login') }}" wire:navigate class="mt-2 flex w-full items-center justify-center rounded-lg bg-primary-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-600">{{ __('auth.btnBackToLogin') }}</a>
            </div>
        @endif
    </div>

    @if ($step < 4)
        <p class="mt-6 text-center text-sm">
            <a href="{{ route('login') }}" wire:navigate class="font-medium text-link hover:underline">{{ __('auth.backToLogin') }}</a>
        </p>
    @endif
</div>
