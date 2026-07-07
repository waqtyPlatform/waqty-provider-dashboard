@php
    $methodMeta = [
        'fingerprint' => ['icon' => 'shield', 'iconClass' => 'bg-info-light text-info'],
        'gps' => ['icon' => 'globe', 'iconClass' => 'bg-success-light text-success'],
        'pin' => ['icon' => 'check-circle-2', 'iconClass' => 'bg-warning-light text-warning'],
        'manual' => ['icon' => 'pencil', 'iconClass' => 'bg-surface-3 text-fg-muted'],
    ];
@endphp

<div class="mx-auto max-w-3xl p-4 sm:p-6">
    <x-ui.page-header :title="__('emp.attendMethods.title')" :subtitle="__('emp.attendMethods.desc')" />

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    @if (count($this->attendanceMethods) === 0)
        <x-ui.card>
            <x-ui.empty-state icon="clock" :title="__('emp.attendMethods.emptyTitle')" :description="__('emp.attendMethods.emptyDesc')" />
        </x-ui.card>
    @else
        <x-ui.card padding="p-0">
            <div class="divide-y divide-line">
                @foreach ($this->attendanceMethods as $m)
                    @php $meta = $methodMeta[$m['type']] ?? ['icon' => 'settings', 'iconClass' => 'bg-surface-3 text-fg-muted']; @endphp
                    <div wire:key="method-{{ $m['uuid'] }}" class="flex flex-wrap items-center gap-4 px-4 py-4 sm:px-5">
                        <div class="grid size-11 shrink-0 place-items-center rounded-xl {{ $meta['iconClass'] }}">
                            <x-icon :name="$meta['icon']" class="size-5" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-medium text-fg">{{ $m['name'] }}</p>
                                <x-ui.badge :color="($m['enabled'] ?? false) ? 'success' : 'neutral'">
                                    {{ ($m['enabled'] ?? false) ? __('emp.attendMethods.enabled') : __('emp.attendMethods.disabled') }}
                                </x-ui.badge>
                            </div>
                            <p class="mt-0.5 text-sm text-fg-muted">
                                @switch($m['type'])
                                    @case('fingerprint')
                                        {{ __('emp.attendMethods.device') }}:
                                        <span dir="ltr" class="font-mono text-xs">{{ $m['device_ip'] ?? '—' }}:{{ $m['device_port'] ?? '—' }}</span>
                                        @break
                                    @case('gps')
                                        {{ __('emp.attendMethods.gpsRadiusSummary', ['radius' => $m['gps_radius'] ?? '—']) }}
                                        @break
                                    @case('pin')
                                        {{ __('emp.attendMethods.pinLengthSummary', ['length' => $m['pin_length'] ?? '—']) }}
                                        @break
                                    @case('manual')
                                        {{ ($m['require_approval'] ?? false) ? __('emp.attendMethods.manualApproval') : __('emp.attendMethods.manualNoApproval') }}
                                        @break
                                @endswitch
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <x-ui.toggle :on="$m['enabled'] ?? false" wire:click="toggleAttendanceMethod('{{ $m['uuid'] }}')" />
                            <x-ui.button variant="secondary" size="sm" icon="settings" wire:click="configure('{{ $m['uuid'] }}')">
                                {{ __('emp.attendMethods.configure') }}
                            </x-ui.button>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    @endif

    <x-ui.slide-over wire="showConfig" :title="__('emp.attendMethods.configureTitle', ['name' => $form_name])">
        <form wire:submit="saveConfig" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                @if ($form_type === 'fingerprint')
                    <x-ui.input :label="__('emp.attendMethods.deviceIp')" wire:model="form_device_ip" dir="ltr" placeholder="192.168.1.50" :error="$errors->first('form_device_ip')" required />
                    <x-ui.input type="number" :label="__('emp.attendMethods.devicePort')" wire:model="form_device_port" dir="ltr" min="1" max="65535" placeholder="4370" :error="$errors->first('form_device_port')" required />
                @elseif ($form_type === 'gps')
                    <x-ui.input type="number" :label="__('emp.attendMethods.gpsRadius')" wire:model="form_gps_radius" min="10" max="5000" :hint="__('emp.attendMethods.gpsRadiusHint')" :error="$errors->first('form_gps_radius')" required />
                @elseif ($form_type === 'pin')
                    <x-ui.input type="number" :label="__('emp.attendMethods.pinLength')" wire:model="form_pin_length" min="4" max="8" :hint="__('emp.attendMethods.pinLengthHint')" :error="$errors->first('form_pin_length')" required />
                @elseif ($form_type === 'manual')
                    <label class="flex items-center justify-between rounded-lg border border-line px-3.5 py-2.5">
                        <span class="text-sm font-medium text-fg">{{ __('emp.attendMethods.requireApproval') }}</span>
                        <x-ui.toggle :on="$form_require_approval" wire:click="$toggle('form_require_approval')" />
                    </label>
                @endif

                <div class="rounded-lg border border-line bg-surface-2 px-3.5 py-3 text-xs text-fg-muted">
                    {{ __('emp.attendMethods.configNote') }}
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="saveConfig">{{ __('common.save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>
</div>
