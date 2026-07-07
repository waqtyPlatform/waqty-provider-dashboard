<x-layouts.guest :title="$title">
    <div class="w-full max-w-md text-center">
        <div class="mx-auto mb-4 grid size-14 place-items-center rounded-2xl bg-primary-500 text-2xl font-bold text-white shadow-primary">و</div>
        <h1 class="text-xl font-semibold text-fg">{{ $title }}</h1>
        <p class="mt-2 text-sm text-fg-muted">{{ __('comingSoon.defaultDesc') }}</p>
        <a href="{{ route('login') }}" wire:navigate class="mt-6 inline-block text-sm font-medium text-link hover:underline">
            {{ __('auth.backToLogin') }}
        </a>
    </div>
</x-layouts.guest>
