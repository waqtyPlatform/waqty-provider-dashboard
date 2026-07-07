@php
    $inputClass = 'w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20';
@endphp

<div class="w-full max-w-md">
    <div class="mb-8 flex flex-col items-center text-center">
        <div class="mb-4 grid size-14 place-items-center rounded-2xl bg-primary-500 text-white shadow-primary">
            <x-icon name="user-cog" class="size-7" />
        </div>
        <h1 class="text-2xl font-semibold text-fg">{{ __('employeePortal.title') }}</h1>
        <p class="mt-1 text-sm text-fg-muted">{{ __('employeePortal.signInWithCreds') }}</p>
    </div>

    <form wire:submit="login" class="rounded-2xl border border-line bg-surface p-6 shadow-md sm:p-8">
        <div class="mb-4">
            <label for="email" class="mb-1.5 block text-sm font-medium text-fg">{{ __('employeePortal.email') }}</label>
            <input id="email" type="email" wire:model="email" dir="ltr" autocomplete="username" placeholder="name@business.com" class="{{ $inputClass }} @error('email') border-error @enderror">
            @error('email') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
        </div>

        <div class="mb-6">
            <label for="password" class="mb-1.5 block text-sm font-medium text-fg">{{ __('employeePortal.password') }}</label>
            <div class="relative">
                <input id="password" type="{{ $showPassword ? 'text' : 'password' }}" wire:model="password" autocomplete="current-password" placeholder="{{ __('employeePortal.passwordPlaceholder') }}" class="{{ $inputClass }} pe-11 @error('password') border-error @enderror">
                <button type="button" wire:click="$toggle('showPassword')" class="absolute inset-y-0 end-0 grid w-11 place-items-center text-fg-subtle hover:text-fg-muted" aria-label="{{ $showPassword ? __('auth.hidePassword') : __('auth.showPassword') }}">
                    @if ($showPassword)
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                    @else
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    @endif
                </button>
            </div>
            @error('password') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" wire:loading.attr="disabled" wire:target="login" class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500/40 disabled:opacity-60">
            <span wire:loading.remove wire:target="login">{{ __('employeePortal.signIn') }}</span>
            <span wire:loading wire:target="login">{{ __('employeePortal.signingIn') }}</span>
        </button>
    </form>

    <p class="mt-6 text-center text-sm">
        <a href="{{ route('login') }}" wire:navigate class="font-medium text-link hover:underline">{{ __('auth.backToLogin') }}</a>
    </p>
</div>
