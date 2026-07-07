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
                                <div x-data="{ o: false }" @click.outside="o = false" class="relative inline-block">
                                    <button @click="o = !o" class="grid size-8 place-items-center rounded-lg text-fg-subtle hover:bg-surface-3"><x-icon name="more-vertical" class="size-4" /></button>
                                    <div x-show="o" x-cloak class="absolute end-0 z-10 mt-1 w-36 overflow-hidden rounded-lg border border-line bg-surface py-1 shadow-lg">
                                        <button wire:click="revoke({{ $d['id'] }})" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-error hover:bg-error-light"><x-icon name="log-out" class="size-4" />{{ __('settings.devices.revoke') }}</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
