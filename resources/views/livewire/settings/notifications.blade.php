@php
    $labels = [
        'newBooking' => __('settings.notifications.newBooking'),
        'cancelBooking' => __('settings.notifications.cancelBooking'),
        'paymentReceived' => __('settings.notifications.paymentReceived'),
        'dailySummary' => __('settings.notifications.dailySummary'),
        'employeeClockIn' => __('settings.notifications.employeeClockIn'),
        'clientBirthday' => __('settings.notifications.clientBirthday'),
    ];
@endphp

<div class="mx-auto max-w-2xl p-6">
    <x-ui.page-header :title="__('settings.notifications.title')" :subtitle="__('settings.notifications.desc')" />

    @if ($fallbackUsed)
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <form wire:submit="save">
        <x-ui.card padding="p-0">
            <div class="grid grid-cols-[1fr_3rem_3rem] items-center gap-x-4 border-b border-line px-5 py-3 text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                <span></span>
                <span class="text-center">{{ __('settings.notifications.push') }}</span>
                <span class="text-center">{{ __('settings.notifications.email') }}</span>
            </div>
            @foreach ($types as $type)
                <div wire:key="ntf-{{ $type }}" class="grid grid-cols-[1fr_3rem_3rem] items-center gap-x-4 border-b border-line px-5 py-3.5 last:border-0">
                    <span class="text-sm font-medium text-fg">{{ $labels[$type] ?? $type }}</span>
                    <div class="flex justify-center">
                        <x-ui.toggle :on="$prefs[$type]['push']" wire:click="$toggle('prefs.{{ $type }}.push')" size="sm" />
                    </div>
                    <div class="flex justify-center">
                        <x-ui.toggle :on="$prefs[$type]['email']" wire:click="$toggle('prefs.{{ $type }}.email')" size="sm" />
                    </div>
                </div>
            @endforeach
        </x-ui.card>

        <div class="mt-6 flex justify-end">
            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('settings.notifications.saveChanges') }}</x-ui.button>
        </div>
    </form>
</div>
