@php
    $distributions = [
        'individual' => __('tipping.individual'),
        'pool' => __('tipping.pool'),
        'split' => __('tipping.split'),
    ];
@endphp

<div class="mx-auto max-w-2xl p-6">
    <x-ui.page-header :title="__('tipping.title')" :subtitle="__('tipping.subtitleFull')" />

    @if ($fallbackUsed)
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <form wire:submit="save" class="space-y-6">
        {{-- Enable --}}
        <x-ui.card>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-fg">{{ __('tipping.enable') }}</p>
                    <p class="text-xs text-fg-subtle">{{ __('tipping.enableDesc') }}</p>
                </div>
                <x-ui.toggle :on="$enabled" wire:click="$toggle('enabled')" />
            </div>
        </x-ui.card>

        {{-- Quick percentages --}}
        <x-ui.card>
            <h2 class="mb-3 font-semibold text-fg">{{ __('tipping.percentages') }}</h2>
            <div class="mb-4 flex flex-wrap gap-2">
                @forelse ($percentages as $pct)
                    <span wire:key="pct-{{ $pct }}" class="inline-flex items-center gap-1.5 rounded-full border border-primary-500/30 bg-primary-50 px-3 py-1 text-sm font-medium text-primary-700 dark:bg-primary-500/10 dark:text-primary-300">
                        {{ $pct }}%
                        <button type="button" wire:click="removePercentage({{ $pct }})" class="text-primary-500 hover:text-error"><x-icon name="x" class="size-3.5" /></button>
                    </span>
                @empty
                    <p class="text-sm text-fg-subtle">—</p>
                @endforelse
            </div>
            <div class="flex items-end gap-2">
                <div class="w-32">
                    <x-ui.input type="number" :label="__('tipping.addPercentage')" wire:model="newPercentage" wire:keydown.enter.prevent="addPercentage" :placeholder="__('tipping.addPercentagePh')" min="1" max="100" :error="$errors->first('newPercentage')" />
                </div>
                <x-ui.button type="button" variant="secondary" wire:click="addPercentage">{{ __('tipping.add') }}</x-ui.button>
            </div>
        </x-ui.card>

        {{-- Custom amount --}}
        <x-ui.card>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-fg">{{ __('tipping.customAmount') }}</p>
                    <p class="text-xs text-fg-subtle">{{ __('tipping.customAmountDesc') }}</p>
                </div>
                <x-ui.toggle :on="$allowCustom" wire:click="$toggle('allowCustom')" />
            </div>
        </x-ui.card>

        {{-- Distribution --}}
        <x-ui.card>
            <h2 class="mb-1 font-semibold text-fg">{{ __('tipping.distribution') }}</h2>
            <p class="mb-3 text-xs text-fg-subtle">{{ __('tipping.distributionLabel') }}</p>
            <div class="space-y-2">
                @foreach ($distributions as $val => $label)
                    <button type="button" wire:click="$set('distribution', '{{ $val }}')"
                        class="flex w-full items-center gap-3 rounded-lg border px-3.5 py-2.5 text-start text-sm transition-colors {{ $distribution === $val ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' : 'border-line text-fg-muted hover:bg-surface-2' }}">
                        <span class="grid size-4 place-items-center rounded-full border {{ $distribution === $val ? 'border-primary-500' : 'border-line' }}">
                            @if ($distribution === $val)<span class="size-2 rounded-full bg-primary-500"></span>@endif
                        </span>
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </x-ui.card>

        <div class="flex justify-end">
            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('settings.saveChanges') }}</x-ui.button>
        </div>
    </form>
</div>
