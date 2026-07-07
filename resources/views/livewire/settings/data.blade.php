<div class="mx-auto max-w-3xl p-6">
    <x-ui.page-header :title="__('settings.data.title')" :subtitle="__('settings.data.desc')" />

    <div class="space-y-6">
        {{-- Export --}}
        <x-ui.card>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-fg">{{ __('settings.data.exportAll') }}</h2>
                    <p class="mt-1 text-sm text-fg-subtle">{{ __('settings.data.exportAllDesc') }}</p>
                </div>
                <x-ui.button icon="rotate-ccw" wire:click="export" wire:loading.attr="disabled" wire:target="export">{{ __('settings.data.exportNow') }}</x-ui.button>
            </div>
        </x-ui.card>

        {{-- Import --}}
        <x-ui.card>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-fg">{{ __('settings.data.importData') }}</h2>
                    <p class="mt-1 text-sm text-fg-subtle">{{ __('settings.data.importDataDesc') }}</p>
                </div>
                <x-ui.button variant="secondary" icon="inbox" wire:click="import" wire:loading.attr="disabled" wire:target="import">{{ __('settings.data.importWizard') }}</x-ui.button>
            </div>
        </x-ui.card>

        {{-- Auto-backup --}}
        <form wire:submit="save" class="space-y-6">
            <x-ui.card>
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-fg">{{ __('settings.data.autoBackup') }}</p>
                        <p class="text-xs text-fg-subtle">{{ __('settings.data.autoBackupDesc') }}</p>
                    </div>
                    <x-ui.toggle :on="$autoBackup" wire:click="$toggle('autoBackup')" />
                </div>
            </x-ui.card>

            <div class="flex justify-end">
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('settings.saveChanges') }}</x-ui.button>
            </div>
        </form>
    </div>
</div>
