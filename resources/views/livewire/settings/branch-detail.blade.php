<div class="mx-auto max-w-3xl p-6">
    <a href="{{ route('settings.branches') }}" wire:navigate class="mb-4 inline-flex items-center gap-1.5 text-sm text-fg-muted hover:text-fg">
        <x-icon name="chevron-left" class="size-4 rtl:rotate-180" />{{ __('settings.branchDetail.back') }}
    </a>

    <x-ui.page-header :title="$form_name ?: __('settings.branches.branchName')" :subtitle="$form_city" />

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    {{-- Tabs --}}
    <div class="mb-5 flex gap-1 border-b border-line">
        @foreach (['general' => 'settings.branchDetail.tabGeneral', 'rooms' => 'settings.branchDetail.tabRooms', 'geofence' => 'settings.branchDetail.tabGeofence'] as $key => $label)
            <button type="button" wire:click="$set('tab', '{{ $key }}')"
                @class([
                    '-mb-px border-b-2 px-4 py-2.5 text-sm font-medium transition',
                    'border-primary-500 text-primary-600' => $tab === $key,
                    'border-transparent text-fg-muted hover:text-fg' => $tab !== $key,
                ])>{{ __($label) }}</button>
        @endforeach
    </div>

    {{-- General --}}
    @if ($tab === 'general')
        <x-ui.card>
            <form wire:submit="saveGeneral" class="space-y-4">
                <x-ui.input :label="__('settings.branches.branchName')" wire:model="form_name" :error="$errors->first('form_name')" />
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ui.input :label="__('settings.branches.phone')" wire:model="form_phone" :error="$errors->first('form_phone')" />
                    <x-ui.input :label="__('settings.branches.city')" wire:model="form_city" :error="$errors->first('form_city')" />
                </div>
                <x-ui.input type="email" :label="__('settings.branches.email')" wire:model="form_email" :error="$errors->first('form_email')" />
                <div class="flex justify-end pt-2">
                    <x-ui.button type="submit">{{ __('settings.branches.saveBranch') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    @endif

    {{-- Rooms --}}
    @if ($tab === 'rooms')
        <x-ui.card>
            <div class="mb-4">
                <h3 class="font-semibold text-fg">{{ __('settings.branchDetail.roomsTitle') }}</h3>
                <p class="text-sm text-fg-muted">{{ __('settings.branchDetail.roomsDesc') }}</p>
            </div>

            @if ($rooms === [])
                <p class="rounded-lg border border-dashed border-line py-8 text-center text-sm text-fg-subtle">{{ __('settings.branchDetail.noRooms') }}</p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($rooms as $room)
                        <li wire:key="room-{{ $room['id'] }}" class="flex items-center justify-between py-3">
                            <div class="flex items-center gap-3">
                                <div class="grid size-9 place-items-center rounded-lg bg-primary-50 text-primary-600"><x-icon name="users" class="size-4.5" /></div>
                                <div>
                                    <p class="font-medium text-fg">{{ $room['name'] }}</p>
                                    <p class="text-xs text-fg-subtle">{{ __('settings.branchDetail.capacity') }}: {{ $room['capacity'] }}</p>
                                </div>
                            </div>
                            <button wire:click="removeRoom('{{ $room['id'] }}')" class="grid size-8 place-items-center rounded-lg text-fg-subtle hover:bg-error-light hover:text-error"><x-icon name="trash-2" class="size-4" /></button>
                        </li>
                    @endforeach
                </ul>
            @endif

            <form wire:submit="addRoom" class="mt-4 flex flex-wrap items-end gap-3 border-t border-line pt-4">
                <div class="min-w-40 flex-1">
                    <x-ui.input :label="__('settings.branchDetail.roomName')" wire:model="room_name" :error="$errors->first('room_name')" />
                </div>
                <div class="w-28">
                    <x-ui.input type="number" min="1" max="100" :label="__('settings.branchDetail.capacity')" wire:model="room_capacity" :error="$errors->first('room_capacity')" />
                </div>
                <x-ui.button type="submit" icon="plus">{{ __('settings.branchDetail.addRoom') }}</x-ui.button>
            </form>
        </x-ui.card>
    @endif

    {{-- Geofence --}}
    @if ($tab === 'geofence')
        <x-ui.card>
            <div class="mb-4">
                <h3 class="font-semibold text-fg">{{ __('settings.branchDetail.geofenceTitle') }}</h3>
                <p class="text-sm text-fg-muted">{{ __('settings.branchDetail.geofenceDesc') }}</p>
            </div>
            <form wire:submit="saveGeofence" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ui.input :label="__('settings.branchDetail.latitude')" wire:model="form_latitude" placeholder="30.0444" :error="$errors->first('form_latitude')" />
                    <x-ui.input :label="__('settings.branchDetail.longitude')" wire:model="form_longitude" placeholder="31.2357" :error="$errors->first('form_longitude')" />
                </div>
                <x-ui.input type="number" min="10" max="5000" :label="__('settings.branchDetail.radius')" wire:model="form_radius" :error="$errors->first('form_radius')" />
                <label class="flex items-center justify-between rounded-lg border border-line px-3.5 py-3">
                    <div>
                        <span class="text-sm font-medium text-fg">{{ __('settings.branchDetail.requireGps') }}</span>
                        <p class="text-xs text-fg-subtle">{{ __('settings.branchDetail.requireGpsDesc') }}</p>
                    </div>
                    <x-ui.toggle :on="$form_require_gps" wire:click="$toggle('form_require_gps')" />
                </label>
                <div class="flex justify-end pt-2">
                    <x-ui.button type="submit">{{ __('settings.branches.saveBranch') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    @endif
</div>
