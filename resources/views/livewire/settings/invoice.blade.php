@php
    $currencies = ['EGP' => 'EGP', 'SAR' => 'SAR', 'AED' => 'AED', 'USD' => 'USD'];
@endphp

<div class="mx-auto max-w-3xl p-6">
    <x-ui.page-header :title="__('settings.invoice.title')" :subtitle="__('settings.invoice.desc')" />

    @if ($fallbackUsed)
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <form wire:submit="save" class="space-y-6">
        {{-- Business identity --}}
        <x-ui.card>
            <h2 class="mb-4 font-semibold text-fg">{{ __('settings.invoice.businessInfo') }}</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input :label="__('settings.invoice.businessName')" wire:model="businessName" :error="$errors->first('businessName')" />
                <x-ui.input :label="__('settings.invoice.taxNumber')" wire:model="taxNumber" :error="$errors->first('taxNumber')" />
                <x-ui.input :label="__('settings.invoice.phone')" wire:model="phone" />
                <x-ui.input :label="__('settings.invoice.address')" wire:model="address" />
            </div>
        </x-ui.card>

        {{-- Numbering / format --}}
        <x-ui.card>
            <h2 class="mb-4 font-semibold text-fg">{{ __('settings.invoice.formatTitle') }}</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input :label="__('settings.invoice.prefix')" wire:model="prefix" :error="$errors->first('prefix')" />
                <x-ui.input type="number" :label="__('settings.invoice.nextNumber')" wire:model="nextNumber" min="1" :error="$errors->first('nextNumber')" />
                <x-ui.input type="number" :label="__('settings.invoice.taxRate')" wire:model="taxRate" min="0" max="100" step="0.1" :error="$errors->first('taxRate')" />
                <x-ui.select :label="__('settings.invoice.currency')" wire:model="currency" :options="$currencies" />
            </div>
        </x-ui.card>

        {{-- Footer --}}
        <x-ui.card>
            <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('settings.invoice.footerTitle') }}</label>
            <textarea wire:model="footerText" rows="2"
                class="w-full rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"></textarea>
        </x-ui.card>

        <div class="flex justify-end">
            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('settings.invoice.saveSettings') }}</x-ui.button>
        </div>
    </form>
</div>
