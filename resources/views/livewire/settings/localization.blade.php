@php
    $timezones = [
        'Africa/Cairo' => __('settings.localization.cairo'),
        'Asia/Riyadh' => __('settings.localization.riyadh'),
        'Asia/Dubai' => __('settings.localization.dubai'),
        'UTC' => __('settings.localization.utc'),
    ];
    $currencies = [
        'EGP' => __('settings.localization.egp'),
        'SAR' => __('settings.localization.sar'),
        'AED' => __('settings.localization.aed'),
        'USD' => __('settings.localization.usd'),
    ];
    $formats = [
        'DD/MM/YYYY' => 'DD/MM/YYYY',
        'MM/DD/YYYY' => 'MM/DD/YYYY',
        'YYYY-MM-DD' => 'YYYY-MM-DD',
    ];
@endphp

<div class="mx-auto max-w-2xl p-6">
    <x-ui.page-header :title="__('settings.localization.title')" :subtitle="__('settings.localization.desc')" />

    <form wire:submit="save">
        <x-ui.card>
            <div class="space-y-4">
                <x-ui.select :label="__('settings.localization.timezone')" wire:model="timezone" :options="$timezones" />
                <x-ui.select :label="__('settings.localization.currency')" wire:model="currency" :options="$currencies" />
                <x-ui.select :label="__('settings.localization.dateFormat')" wire:model="dateFormat" :options="$formats" />
            </div>
        </x-ui.card>

        <div class="mt-6 flex justify-end">
            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('settings.localization.saveChanges') }}</x-ui.button>
        </div>
    </form>
</div>
