@php
    $swatches = ['#00b166', '#3b82f6', '#8b5cf6', '#ef4444', '#f59e0b', '#06b6d4', '#ec4899', '#0ea5e9'];
    $themes = [
        'light' => __('settings.appearance.themeLight'),
        'dark' => __('settings.appearance.themeDark'),
        'system' => __('settings.appearance.themeSystem'),
    ];
@endphp

<div class="mx-auto max-w-2xl p-6">
    <x-ui.page-header :title="__('settings.appearance.title')" :subtitle="__('settings.appearance.desc')" />

    <form wire:submit="save" class="space-y-6">
        {{-- Theme — applied instantly on the client, mirroring the top-bar toggle. --}}
        <x-ui.card>
            <div
                x-data="{
                    theme: (document.cookie.match(/(?:^|;\s*)waqty_theme=([^;]+)/) || [])[1] || 'system',
                    apply() {
                        document.cookie = 'waqty_theme=' + this.theme + ';path=/;max-age=31536000;samesite=lax';
                        const r = this.theme === 'system' ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : this.theme;
                        document.documentElement.setAttribute('data-theme', r);
                    }
                }"
            >
                <label class="mb-2 block text-sm font-medium text-fg">{{ __('settings.appearance.theme') }}</label>
                <div class="grid grid-cols-3 gap-2">
                    @foreach ($themes as $val => $lbl)
                        <button type="button"
                            @click="theme = '{{ $val }}'; apply()"
                            :class="theme === '{{ $val }}' ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' : 'border-line text-fg-muted hover:bg-surface-2'"
                            class="rounded-lg border px-3 py-2.5 text-sm font-medium transition-colors">
                            {{ $lbl }}
                        </button>
                    @endforeach
                </div>
            </div>
        </x-ui.card>

        {{-- Brand colour --}}
        <x-ui.card>
            <label class="mb-3 block text-sm font-medium text-fg">{{ __('settings.appearance.brandColor') }}</label>
            <div class="flex flex-wrap gap-2.5">
                @foreach ($swatches as $hex)
                    <button type="button" wire:click="$set('brandColor', '{{ $hex }}')"
                        class="size-9 rounded-full ring-2 ring-offset-2 ring-offset-surface transition {{ $brandColor === $hex ? 'ring-fg' : 'ring-transparent' }}"
                        style="background-color: {{ $hex }}"
                        aria-label="{{ $hex }}"></button>
                @endforeach
            </div>
        </x-ui.card>

        {{-- Layout toggles --}}
        <x-ui.card>
            <div class="divide-y divide-line">
                <div class="flex items-center justify-between gap-4 py-3.5 first:pt-0">
                    <p class="text-sm font-medium text-fg">{{ __('settings.appearance.compactSidebar') }}</p>
                    <x-ui.toggle :on="$compactSidebar" wire:click="$toggle('compactSidebar')" />
                </div>
                <div class="flex items-center justify-between gap-4 py-3.5 last:pb-0">
                    <p class="text-sm font-medium text-fg">{{ __('settings.appearance.showAnimations') }}</p>
                    <x-ui.toggle :on="$showAnimations" wire:click="$toggle('showAnimations')" />
                </div>
            </div>
        </x-ui.card>

        <div class="flex justify-end">
            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('settings.appearance.saveChanges') }}</x-ui.button>
        </div>
    </form>
</div>
