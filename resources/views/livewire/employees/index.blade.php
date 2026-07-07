@php $staff = $provider->terminology()['staff']; @endphp

<div class="p-6">
    <x-ui.page-header :title="__('sidebar.employees')" :subtitle="__('employees.totalTeam')">
        <x-slot:actions>
            <x-ui.button icon="user-plus" wire:click="openCreate">{{ __('employees.inviteStaff') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') ?? 'Showing sample data — the live API is unavailable.' }}</x-ui.alert>
    @endif

    {{-- KPIs --}}
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.kpi-card :label="__('employees.totalTeam')" :value="$this->kpis['total']" icon="users" />
        <x-ui.kpi-card :label="__('employees.available')" :value="$this->kpis['active']" icon="check-circle-2" iconClass="bg-success-light text-success" />
        <x-ui.kpi-card :label="__('common.blocked')" :value="$this->kpis['blocked']" icon="ban" iconClass="bg-error-light text-error" />
        <x-ui.kpi-card :label="__('employees.branch')" :value="$this->kpis['branches']" icon="building-2" iconClass="bg-info-light text-info" />
    </div>

    {{-- Controls --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="relative min-w-64 flex-1">
            <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-fg-subtle">
                <x-icon name="search" class="size-4" />
            </span>
            <input
                type="search"
                id="employees-search"
                aria-label="{{ __('common.search') }}"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('employees.search') ?? __('common.search') }}"
                class="w-full rounded-lg border border-line bg-surface py-2.5 pe-3.5 ps-10 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            >
        </div>
        <select wire:model.live="statusFilter" id="employees-status" aria-label="{{ __('common.status') }}" class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
            <option value="all">{{ __('common.all') }}</option>
            <option value="active">{{ __('common.active') }}</option>
            <option value="inactive">{{ __('common.inactive') }}</option>
            <option value="blocked">{{ __('common.blocked') }}</option>
        </select>
    </div>

    {{-- Table --}}
    <x-ui.card padding="p-0">
        @if ($this->total === 0)
            <x-ui.empty-state
                :title="__('employees.noEmployeesFound')"
                :description="__('employees.noEmployeesDesc')"
                icon="user-cog"
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-start text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ $staff }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('employees.phoneOrEmail') ?? 'Contact' }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('employees.branch') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('employees.systemRole') ?? 'Role' }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('common.status') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->paginated as $e)
                            <tr wire:key="emp-{{ $e->uuid }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <x-ui.avatar :name="$e->name" />
                                        <div class="min-w-0">
                                            <div class="truncate font-medium text-fg">{{ $e->name }}</div>
                                            @if ($e->position)
                                                <span class="text-xs text-fg-subtle">{{ $e->position }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5 text-fg-muted" dir="ltr"><x-icon name="phone" class="size-3.5" />{{ $e->phone ?: '—' }}</div>
                                    @if ($e->email)
                                        <div class="mt-0.5 flex items-center gap-1.5 text-xs text-fg-subtle" dir="ltr"><x-icon name="mail" class="size-3.5" />{{ $e->email }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-fg-muted">
                                    <div class="flex items-center gap-1.5"><x-icon name="building-2" class="size-3.5 text-fg-subtle" />{{ $e->branchName() ?: '—' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <x-ui.badge :color="match($e->role) { 'admin' => 'purple', 'manager' => 'info', default => 'neutral' }">
                                        {{ match($e->role) { 'admin' => __('employees.admin'), 'manager' => __('employees.roleManager'), default => __('employees.roleStaff') } }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <x-ui.toggle :on="$e->active && ! $e->blocked" :disabled="$e->blocked" wire:click="toggleActive('{{ $e->uuid }}')" size="sm" />
                                        @if ($e->blocked)
                                            <x-ui.badge color="error">{{ __('common.blocked') }}</x-ui.badge>
                                        @else
                                            <span class="text-xs {{ $e->active ? 'text-success' : 'text-fg-subtle' }}">{{ $e->active ? __('common.active') : __('common.inactive') }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <div x-data="{ o: false }" @click.outside="o = false" class="relative inline-block">
                                        <button @click="o = !o" class="grid size-8 place-items-center rounded-lg text-fg-subtle hover:bg-surface-3"><x-icon name="more-vertical" class="size-4" /></button>
                                        <div x-show="o" x-cloak class="absolute end-0 z-10 mt-1 w-44 overflow-hidden rounded-lg border border-line bg-surface py-1 shadow-lg">
                                            <button wire:click="openEdit('{{ $e->uuid }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-fg hover:bg-surface-2"><x-icon name="pencil" class="size-4" />{{ __('common.edit') }}</button>
                                            <button wire:click="toggleBlock('{{ $e->uuid }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-fg hover:bg-surface-2"><x-icon name="ban" class="size-4" />{{ $e->blocked ? __('common.unblock') : __('common.block') }}</button>
                                            <button wire:click="confirmDelete('{{ $e->uuid }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-error hover:bg-error-light"><x-icon name="trash-2" class="size-4" />{{ __('common.delete') }}</button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-ui.pagination :page="$currentPage" :perPage="$perPage" :total="$this->total" field="currentPage" />
        @endif
    </x-ui.card>

    {{-- Create / edit slide-over --}}
    <x-ui.slide-over wire="showForm" :title="$editingUuid ? __('employees.editEmployeeTitle') : __('employees.addEmployeeTitle')">
            <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
                <div class="flex-1 space-y-4 p-5">
                    <x-ui.input :label="__('employees.fullName')" wire:model="form_name" :error="$errors->first('form_name')" />
                    <x-ui.input :label="__('employees.emailOption')" type="email" wire:model="form_email" dir="ltr" :error="$errors->first('form_email')" />
                    <x-ui.input :label="__('employees.phoneOption')" wire:model="form_phone" dir="ltr" :error="$errors->first('form_phone')" />
                    <x-ui.select :label="__('employees.branch')" wire:model="form_branch" :options="$this->branchOptions" :placeholder="__('employees.selectBranch')" :error="$errors->first('form_branch')" />
                    <x-ui.select :label="__('employees.systemRole')" wire:model="form_role" :options="['admin' => __('employees.admin'), 'manager' => __('employees.roleManager'), 'staff' => __('employees.roleStaff')]" :error="$errors->first('form_role')" />
                    <x-ui.input :label="__('employees.jobTitle')" wire:model="form_position" :error="$errors->first('form_position')" />
                    @unless ($editingUuid)
                        <x-ui.input :label="__('employees.password')" type="password" wire:model="form_password" dir="ltr" :error="$errors->first('form_password')" :hint="__('employees.passwordPh')" />
                    @endunless
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                    <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('common.save') }}</x-ui.button>
                </div>
            </form>
    </x-ui.slide-over>

    {{-- Delete confirmation --}}
    <x-ui.confirm-dialog wire="showDelete" :title="__('employees.deleteEmployeeTitle')" :description="__('employees.deleteWarning')" action="deleteEmployee" :actionLabel="__('common.delete')" />
</div>
