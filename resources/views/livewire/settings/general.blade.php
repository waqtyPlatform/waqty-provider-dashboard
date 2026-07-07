<div class="mx-auto max-w-3xl p-6">
    <x-ui.page-header :title="__('settings.title')" />

    <form wire:submit="save" class="space-y-6">
        {{-- Booking preferences --}}
        <x-ui.card>
            <h2 class="mb-4 font-semibold text-fg">{{ __('sidebar.bookings') }}</h2>
            <div class="divide-y divide-line">
                @foreach ([
                    ['onlineBooking', __('settings.onlineBooking'), __('settings.onlineBookingDesc')],
                    ['walkIn', __('settings.walkIn'), __('settings.walkInDesc')],
                    ['autoConfirm', __('settings.autoConfirm'), __('settings.autoConfirmDesc')],
                    ['smsReminders', __('settings.smsReminders'), __('settings.smsRemindersDesc')],
                    ['requireDeposit', 'Require deposit', 'Require an upfront deposit to confirm a booking.'],
                ] as [$model, $label, $desc])
                    <div class="flex items-center justify-between gap-4 py-3.5">
                        <div>
                            <p class="text-sm font-medium text-fg">{{ $label }}</p>
                            <p class="text-xs text-fg-subtle">{{ $desc }}</p>
                        </div>
                        <x-ui.toggle :on="$this->{$model}" wire:click="$toggle('{{ $model }}')" />
                    </div>
                @endforeach
            </div>
        </x-ui.card>

        {{-- Scheduling defaults --}}
        <x-ui.card>
            <h2 class="mb-4 font-semibold text-fg">{{ __('reports.tabOverview') }}</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input type="number" label="Default gap between bookings (min)" wire:model="defaultGap" min="0" max="120" :error="$errors->first('defaultGap')" />
                <x-ui.input type="number" label="Cancellation window (hours)" wire:model="cancellationWindow" min="0" max="168" :error="$errors->first('cancellationWindow')" />
            </div>
        </x-ui.card>

        <div class="flex justify-end">
            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('settings.saveChanges') }}</x-ui.button>
        </div>
    </form>
</div>
