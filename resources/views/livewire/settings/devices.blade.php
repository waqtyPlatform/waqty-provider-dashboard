@php
    $typeMeta = [
        'desktop' => ['label' => __('settings.devices.typeDesktop'), 'color' => 'info'],
        'mobile' => ['label' => __('settings.devices.typeMobile'), 'color' => 'success'],
        'tablet' => ['label' => __('settings.devices.typeTablet'), 'color' => 'neutral'],
    ];
@endphp

<div class="mx-auto max-w-4xl p-6">
    <x-ui.page-header :title="__('settings.devices.title')" :subtitle="__('settings.devices.desc')" />

    <x-ui.card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-sm">
                <thead>
                    <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.devices.colDevice') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.devices.colType') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.devices.colLastActive') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.devices.colLocation') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->items as $d)
                        <tr wire:key="device-{{ $d['id'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                            <td class="px-4 py-3 font-medium text-fg">{{ $d['name'] }}</td>
                            <td class="px-4 py-3">
                                <x-ui.badge :color="$typeMeta[$d['type']]['color'] ?? 'neutral'">{{ $typeMeta[$d['type']]['label'] ?? $d['type'] }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-3 text-fg-muted">{{ $d['lastActive'] }}</td>
                            <td class="px-4 py-3 text-fg-muted">{{ $d['location'] }}</td>
                            <td class="px-4 py-3 text-end">
                                <x-ui.dropdown>
                                    <x-ui.dropdown-item icon="log-out" wire:click="revoke({{ $d['id'] }})" destructive>{{ __('settings.devices.revoke') }}</x-ui.dropdown-item>
                                </x-ui.dropdown>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
