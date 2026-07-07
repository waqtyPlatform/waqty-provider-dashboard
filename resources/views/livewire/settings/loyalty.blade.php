<div class="mx-auto max-w-3xl p-6">
    <x-ui.page-header :title="__('loyalty.title')" :subtitle="__('loyalty.subtitleFull')" />

    @if ($fallbackUsed)
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <form wire:submit="save" class="space-y-6">
        {{-- Enable --}}
        <x-ui.card>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-fg">{{ __('loyalty.enable') }}</p>
                    <p class="text-xs text-fg-subtle">{{ __('loyalty.enableHint') }}</p>
                </div>
                <x-ui.toggle :on="$enabled" wire:click="$toggle('enabled')" />
            </div>
        </x-ui.card>

        {{-- Earning rules --}}
        <x-ui.card>
            <h2 class="mb-4 font-semibold text-fg">{{ __('loyalty.earningRules') }}</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input type="number" :label="__('loyalty.pointsPerEgp')" wire:model="pointsPerEgp" min="0" step="0.1" :error="$errors->first('pointsPerEgp')" />
                <x-ui.input type="number" :label="__('loyalty.pointsPerBooking')" wire:model="pointsPerBooking" min="0" :error="$errors->first('pointsPerBooking')" />
                <x-ui.input type="number" :label="__('loyalty.referralBonus')" wire:model="referralBonus" min="0" :error="$errors->first('referralBonus')" />
                <x-ui.input type="number" :label="__('loyalty.redemptionRate')" wire:model="redemptionRate" min="1" :error="$errors->first('redemptionRate')" />
            </div>
        </x-ui.card>

        {{-- Tiers --}}
        <x-ui.card>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold text-fg">{{ __('loyalty.tiers') }}</h2>
                <x-ui.button type="button" variant="secondary" size="sm" icon="plus" wire:click="addTier">{{ __('loyalty.addTier') }}</x-ui.button>
            </div>
            <div class="space-y-3">
                @foreach ($tiers as $i => $tier)
                    <div wire:key="tier-{{ $i }}" class="flex flex-wrap items-end gap-3 rounded-lg border border-line p-3">
                        <input type="color" wire:model="tiers.{{ $i }}.color" class="h-9 w-10 shrink-0 cursor-pointer rounded border border-line bg-surface" />
                        <div class="min-w-[8rem] flex-1">
                            <x-ui.input :label="__('loyalty.tierName')" wire:model="tiers.{{ $i }}.name" :error="$errors->first('tiers.'.$i.'.name')" />
                        </div>
                        <div class="w-28">
                            <x-ui.input type="number" :label="__('loyalty.tierMinPoints')" wire:model="tiers.{{ $i }}.min_points" min="0" :error="$errors->first('tiers.'.$i.'.min_points')" />
                        </div>
                        <div class="w-24">
                            <x-ui.input type="number" :label="__('loyalty.tierDiscount')" wire:model="tiers.{{ $i }}.discount" min="0" max="100" step="0.5" :error="$errors->first('tiers.'.$i.'.discount')" />
                        </div>
                        <button type="button" wire:click="removeTier({{ $i }})" class="grid size-9 shrink-0 place-items-center rounded-lg text-fg-subtle hover:bg-error-light hover:text-error"><x-icon name="trash-2" class="size-4" /></button>
                    </div>
                @endforeach
            </div>
        </x-ui.card>

        <div class="flex justify-end">
            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('settings.saveChanges') }}</x-ui.button>
        </div>
    </form>
</div>
