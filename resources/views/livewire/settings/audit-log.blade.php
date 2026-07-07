<div class="mx-auto max-w-4xl p-6">
    <x-ui.page-header :title="__('settings.auditLog.title')" :subtitle="__('settings.auditLog.desc')" />

    <div class="mb-4">
        <div class="relative min-w-64">
            <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-fg-subtle"><x-icon name="search" class="size-4" /></span>
            <input type="search" id="audit-search" aria-label="{{ __('common.search') }}" wire:model.live="search" placeholder="{{ __('common.search') }}"
                class="w-full rounded-lg border border-line bg-surface py-2.5 pe-3.5 ps-10 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
        </div>
    </div>

    <x-ui.card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] text-sm">
                <thead>
                    <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.auditLog.colAction') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('settings.auditLog.colUser') }}</th>
                        <th class="px-4 py-3 text-end font-semibold">{{ __('settings.auditLog.colTime') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->filtered as $row)
                        <tr wire:key="audit-{{ $row['id'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                            <td class="px-4 py-3 font-medium text-fg">{{ $row['action'] }}</td>
                            <td class="px-4 py-3 text-fg-muted">{{ $row['user'] }}</td>
                            <td class="px-4 py-3 text-end tabular-nums text-fg-subtle">{{ $row['time'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-10 text-center text-fg-subtle">{{ __('common.noData') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
