@php
    $emp = $this->selectedEmployee;
    $roleLabels = [
        'admin' => __('emp.perms.role.admin'),
        'manager' => __('emp.perms.role.manager'),
        'staff' => __('emp.perms.role.staff'),
    ];
    $actionLabels = [
        'view' => __('emp.perms.actView'),
        'create' => __('emp.perms.actCreate'),
        'edit' => __('emp.perms.actEdit'),
        'delete' => __('emp.perms.actDelete'),
    ];
@endphp

<div class="mx-auto max-w-5xl p-4 sm:p-6">
    <x-ui.page-header :title="__('emp.perms.title')" :subtitle="__('emp.perms.subtitle')">
        <x-slot:actions>
            <x-ui.badge color="amber">
                <x-icon name="shield" class="size-3.5" />{{ __('emp.perms.adminOnly') }}
            </x-ui.badge>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.alert type="info" class="mb-4">{{ __('emp.perms.localNote') }}</x-ui.alert>

    {{-- Employee picker --}}
    <x-ui.card class="mb-5">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="w-full sm:max-w-xs">
                <x-ui.select
                    :label="__('emp.perms.selectEmployee')"
                    wire:model.live="selectedUuid"
                    :placeholder="__('emp.perms.selectPlaceholder')"
                >
                    @foreach ($employees as $e)
                        <option value="{{ $e['uuid'] }}">{{ $e['name'] }} — {{ $e['position'] }}</option>
                    @endforeach
                </x-ui.select>
            </div>

            @if ($emp)
                <div class="flex items-center gap-3">
                    <x-ui.avatar :name="$emp['name']" size="size-11" />
                    <div class="min-w-0">
                        <p class="font-medium text-fg">{{ $emp['name'] }}</p>
                        <p class="flex flex-wrap items-center gap-1.5 text-xs text-fg-subtle">
                            <span>{{ $emp['position'] }}</span>
                            <span class="text-line">·</span>
                            <span class="inline-flex items-center gap-1">
                                <x-icon name="shield" class="size-3.5" />{{ __('emp.perms.baseRole') }}: {{ $roleLabels[$emp['role']] ?? $emp['role'] }}
                            </span>
                        </p>
                    </div>
                    @if ($this->overrideCount > 0)
                        <x-ui.badge color="warning">{{ __('emp.perms.overridesCount', ['count' => $this->overrideCount]) }}</x-ui.badge>
                    @else
                        <x-ui.badge color="neutral">{{ __('emp.perms.noOverrides') }}</x-ui.badge>
                    @endif
                </div>
            @endif
        </div>
    </x-ui.card>

    @if (! $emp)
        <x-ui.card>
            <x-ui.empty-state :title="__('emp.perms.emptyTitle')" :description="__('emp.perms.emptyDesc')" icon="shield" />
        </x-ui.card>
    @else
        {{-- Override matrix --}}
        <x-ui.card padding="p-0" class="overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead>
                        <tr class="border-b border-line bg-surface-2 text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.perms.colModule') }}</th>
                            @foreach (\App\Livewire\Employees\Permissions::ACTIONS as $action)
                                <th class="px-2 py-3 text-center font-semibold">{{ $actionLabels[$action] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (\App\Livewire\Employees\Permissions::MODULES as $module)
                            <tr wire:key="perm-{{ $module }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-fg">{{ __('emp.perms.mod.'.$module) }}</p>
                                    <div class="mt-1 flex gap-1">
                                        <button type="button" wire:click="setRow('{{ $module }}', 'full')" class="rounded px-1.5 py-0.5 text-[11px] text-primary-600 hover:bg-primary-50">{{ __('emp.perms.levelFull') }}</button>
                                        <button type="button" wire:click="setRow('{{ $module }}', 'none')" class="rounded px-1.5 py-0.5 text-[11px] text-fg-muted hover:bg-surface-3">{{ __('emp.perms.levelNone') }}</button>
                                        <button type="button" wire:click="setRow('{{ $module }}', 'role')" class="rounded px-1.5 py-0.5 text-[11px] text-fg-muted hover:bg-surface-3">{{ __('emp.perms.levelRole') }}</button>
                                    </div>
                                </td>
                                @foreach (\App\Livewire\Employees\Permissions::ACTIONS as $action)
                                    <td class="px-2 py-3 text-center">
                                        <label class="relative inline-flex cursor-pointer items-center justify-center rounded-lg p-1.5 {{ $this->isOverridden($module, $action) ? 'bg-warning-light ring-1 ring-warning/40' : '' }}">
                                            <input type="checkbox" wire:model.live="form_perms.{{ $module }}.{{ $action }}"
                                                class="size-4 rounded border-line text-primary-600 focus:ring-2 focus:ring-primary-500/30" />
                                            @if ($this->isOverridden($module, $action))
                                                <span class="absolute -end-0.5 -top-0.5 size-2 rounded-full bg-warning" title="{{ __('emp.perms.overrideHint') }}"></span>
                                            @endif
                                        </label>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Footer: legend + actions --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-3.5">
                <p class="flex items-center gap-2 text-xs text-fg-subtle">
                    <span class="inline-block size-2.5 rounded-full bg-warning"></span>{{ __('emp.perms.legendOverride') }}
                </p>
                <div class="flex items-center gap-2">
                    <x-ui.button type="button" variant="secondary" size="sm" wire:click="resetAll" icon="rotate-ccw">{{ __('emp.perms.resetAll') }}</x-ui.button>
                    <x-ui.button type="button" size="sm" wire:click="save" icon="check" wire:loading.attr="disabled" wire:target="save">{{ __('emp.perms.save') }}</x-ui.button>
                </div>
            </div>
        </x-ui.card>
    @endif
</div>
