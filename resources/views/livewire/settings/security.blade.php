<div class="mx-auto max-w-2xl p-6">
    <x-ui.page-header :title="__('settings.security.title')" :subtitle="__('settings.security.desc')" />

    <form wire:submit="save" class="space-y-6">
        <x-ui.card>
            <div class="divide-y divide-line">
                @foreach ([
                    ['twoFactor', __('settings.security.twoFactor')],
                    ['passwordChange', __('settings.security.passwordChange')],
                    ['lockAttempts', __('settings.security.lockAttempts')],
                ] as [$model, $label])
                    <div class="flex items-center justify-between gap-4 py-3.5 first:pt-0 last:pb-0">
                        <p class="text-sm font-medium text-fg">{{ $label }}</p>
                        <x-ui.toggle :on="$this->{$model}" wire:click="$toggle('{{ $model }}')" />
                    </div>
                @endforeach
            </div>
        </x-ui.card>

        <x-ui.card>
            <div class="max-w-xs">
                <x-ui.input type="number" :label="__('settings.security.sessionTimeout')" wire:model="sessionTimeout" min="5" max="480" :error="$errors->first('sessionTimeout')" />
            </div>
        </x-ui.card>

        <div class="flex justify-end">
            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('settings.security.saveChanges') }}</x-ui.button>
        </div>
    </form>
</div>
