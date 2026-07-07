@php $labels = $this->dayLabels(); @endphp

<div class="mx-auto max-w-3xl p-6">
    <x-ui.page-header :title="__('settings.hours.title')" :subtitle="__('settings.hours.desc')" />

    @if ($fallbackUsed)
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <form wire:submit="save">
        <x-ui.card>
            <h2 class="mb-4 font-semibold text-fg">{{ __('settings.hours.standardHours') }}</h2>
            <div class="divide-y divide-line">
                @foreach ($days as $i => $day)
                    <div wire:key="day-{{ $i }}" class="flex flex-wrap items-center gap-4 py-3.5">
                        <span class="w-28 shrink-0 text-sm font-medium text-fg">{{ $labels[$day['day']] ?? '—' }}</span>
                        <label class="flex items-center gap-2">
                            <x-ui.toggle :on="! $day['is_closed']" wire:click="toggleClosed({{ $i }})" size="sm" />
                            <span class="text-xs {{ $day['is_closed'] ? 'text-fg-subtle' : 'text-success' }}">{{ $day['is_closed'] ? __('settings.hours.closed') : __('common.active') }}</span>
                        </label>
                        @unless ($day['is_closed'])
                            <div class="ms-auto flex items-center gap-2">
                                <input type="time" wire:model="days.{{ $i }}.open_time" class="rounded-lg border border-line bg-surface px-2.5 py-1.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
                                <span class="text-sm text-fg-subtle">{{ __('settings.hours.to') }}</span>
                                <input type="time" wire:model="days.{{ $i }}.close_time" class="rounded-lg border border-line bg-surface px-2.5 py-1.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
                            </div>
                        @else
                            <span class="ms-auto text-sm text-fg-subtle">{{ __('settings.hours.closed') }}</span>
                        @endunless
                    </div>
                @endforeach
            </div>
        </x-ui.card>

        <div class="mt-6 flex justify-end">
            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('settings.saveChanges') }}</x-ui.button>
        </div>
    </form>
</div>
