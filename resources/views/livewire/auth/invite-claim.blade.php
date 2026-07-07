@php
    $inputClass = 'w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20';
    $btnClass = 'mt-1 flex w-full items-center justify-center gap-2 rounded-lg bg-primary-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500/40 disabled:opacity-60';
@endphp

<div class="w-full max-w-md">
    <div class="mb-6 flex flex-col items-center text-center">
        <div class="mb-4 grid size-14 place-items-center rounded-2xl bg-primary-500 text-2xl font-bold text-white shadow-primary">و</div>
        <p class="mb-1 text-xs font-medium text-primary-600">{{ __('invite.brand') }}</p>
        <h1 class="text-2xl font-semibold text-fg">
            {{ match ($step) { 1 => __('invite.acceptTitle'), 2 => __('invite.completeTitle'), default => __('invite.doneTitle') } }}
        </h1>
        <p class="mt-1 text-sm text-fg-muted">
            {{ match ($step) { 1 => __('invite.acceptSubtitle'), 2 => __('invite.completeSubtitle'), default => __('invite.toastSetupComplete') } }}
        </p>
    </div>

    {{-- Step indicator --}}
    @if ($step < 3)
        <div class="mb-6 flex items-center justify-center gap-2">
            @foreach ([1, 2] as $s)
                <span class="h-1.5 rounded-full transition-all {{ $s === $step ? 'w-8 bg-primary-500' : ($s < $step ? 'w-8 bg-primary-300' : 'w-4 bg-line') }}"></span>
            @endforeach
        </div>
    @endif

    <div class="rounded-2xl border border-line bg-surface p-6 shadow-md sm:p-8">
        {{-- Step 1: verify invited phone --}}
        @if ($step === 1)
            <form wire:submit="sendCode" class="space-y-4">
                <div>
                    <label for="phone" class="mb-1.5 block text-sm font-medium text-fg">{{ __('invite.invitedPhone') }}</label>
                    <input id="phone" type="tel" wire:model="phone" dir="ltr" inputmode="numeric" maxlength="11" placeholder="01XXXXXXXXX" class="{{ $inputClass }} @error('phone') border-error @enderror">
                    @error('phone') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                </div>
                <button type="submit" wire:loading.attr="disabled" wire:target="sendCode" class="{{ $btnClass }}">
                    <span wire:loading.remove wire:target="sendCode">{{ __('invite.sendCode') }}</span>
                    <span wire:loading wire:target="sendCode">{{ __('onboarding.sending') }}</span>
                </button>
            </form>
        @endif

        {{-- Step 2: OTP + full name --}}
        @if ($step === 2)
            <form wire:submit="verify" class="space-y-4">
                <div>
                    <label for="fullName" class="mb-1.5 block text-sm font-medium text-fg">{{ __('invite.fullName') }}</label>
                    <input id="fullName" type="text" wire:model="fullName" placeholder="{{ __('onboarding.fullNamePlaceholder') }}" class="{{ $inputClass }} @error('fullName') border-error @enderror">
                    @error('fullName') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="otp" class="mb-1.5 block text-sm font-medium text-fg">{{ __('invite.enterCode') }}</label>
                    <input id="otp" type="text" wire:model="otp" inputmode="numeric" maxlength="6" dir="ltr" placeholder="000000" class="{{ $inputClass }} text-center font-mono text-lg tracking-[0.5em] @error('otp') border-error @enderror">
                    @error('otp') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                </div>
                <p class="rounded-lg bg-surface-2 px-3 py-2 text-center text-xs text-fg-subtle">{{ __('onboarding.demoTip') }}<span class="font-mono font-semibold text-fg">123456</span></p>
                <button type="submit" wire:loading.attr="disabled" wire:target="verify" class="{{ $btnClass }}">
                    <span wire:loading.remove wire:target="verify">{{ __('invite.joinWorkspace') }}</span>
                    <span wire:loading wire:target="verify">{{ __('onboarding.verifying') }}</span>
                </button>
                <div class="flex items-center justify-between">
                    <button type="button" wire:click="back" class="text-sm text-fg-muted hover:text-fg">{{ __('onboarding.back') }}</button>
                    <button type="button" wire:click="sendCode" class="text-xs font-medium text-link hover:underline">{{ __('onboarding.resendCode') }}</button>
                </div>
            </form>
        @endif

        {{-- Step 3: done --}}
        @if ($step === 3)
            <div class="flex flex-col items-center text-center">
                <div class="mb-4 grid size-14 place-items-center rounded-full bg-success-light text-success"><x-icon name="check-circle-2" class="size-7" /></div>
                <a href="{{ route('employee-portal.login') }}" wire:navigate class="mt-2 flex w-full items-center justify-center gap-2 rounded-lg bg-primary-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-600">
                    <x-icon name="user-cog" class="size-4" />{{ __('invite.goToPortal') }}
                </a>
            </div>
        @endif
    </div>
</div>
