@php use App\Support\Money; @endphp

<div class="p-6">
    <x-ui.page-header :title="__('sidebar.clients')" :subtitle="__('dash.welcome')">
        <x-slot:actions>
            <x-ui.button icon="user-plus" wire:click="openCreate">{{ __('customers.addClient') ?? 'Add Client' }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') ?? 'Showing sample data — the live API is unavailable.' }}</x-ui.alert>
    @endif

    {{-- KPIs --}}
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.kpi-card :label="__('customers.totalClients')" :value="$this->kpis['total']" icon="users" />
        <x-ui.kpi-card :label="__('customers.vipClients')" :value="$this->kpis['vip']" icon="star" iconClass="bg-warning-light text-warning" />
        <x-ui.kpi-card :label="__('customers.newThisMonth')" :value="$this->kpis['new']" icon="user-plus" iconClass="bg-info-light text-info" />
        <x-ui.kpi-card :label="__('customers.inactive')" :value="$this->kpis['inactive']" icon="alert-triangle" iconClass="bg-error-light text-error" />
    </div>

    {{-- Controls --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="relative min-w-64 flex-1">
            <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-fg-subtle">
                <x-icon name="search" class="size-4" />
            </span>
            <input
                type="search"
                id="customers-search"
                aria-label="{{ __('common.search') }}"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('common.search') }}"
                class="w-full rounded-lg border border-line bg-surface py-2.5 pe-3.5 ps-10 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            >
        </div>
        <select wire:model.live="groupFilter" id="customers-group" aria-label="{{ __('customers.allGroups') }}" class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
            <option value="all">{{ __('customers.allGroups') }}</option>
            <option value="vip">{{ __('customers.groupVip') }}</option>
            <option value="regular">{{ __('customers.groupRegular') }}</option>
            <option value="new">{{ __('customers.groupNew') }}</option>
        </select>
    </div>

    {{-- Table --}}
    <x-ui.card padding="p-0">
        @if ($this->total === 0)
            <x-ui.empty-state
                :title="__('customers.noClients')"
                :description="__('customers.noClientsDesc')"
                icon="users"
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-start text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('customers.colClient') ?? 'Client' }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('customers.colContact') ?? 'Contact' }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('customers.colGroup') ?? 'Group' }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('dash.colVisits') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('customers.colTotalSpend') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('customers.colLastVisit') ?? 'Last Visit' }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->paginated as $c)
                            <tr wire:key="cust-{{ $c->uuid }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <x-ui.avatar :name="$c->name" />
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <span class="truncate font-medium text-fg">{{ $c->name }}</span>
                                                @if ($c->vip)
                                                    <x-icon name="star" class="size-3.5 text-warning" />
                                                @endif
                                            </div>
                                            <span class="text-xs text-fg-subtle">{{ $c->uuid }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5 text-fg-muted"><x-icon name="phone" class="size-3.5" />{{ $c->phone ?: '—' }}</div>
                                    @if ($c->email)
                                        <div class="mt-0.5 flex items-center gap-1.5 text-xs text-fg-subtle"><x-icon name="mail" class="size-3.5" />{{ $c->email }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <x-ui.badge :color="$c->vip ? 'amber' : (strtolower($c->groupName()) === 'new' ? 'info' : 'neutral')">{{ $c->groupName() }}</x-ui.badge>
                                    @if ($c->allergies)
                                        <span class="ms-1 inline-flex" title="{{ $c->allergies }}"><x-icon name="alert-triangle" class="size-3.5 text-error" /></span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-end font-medium tabular-nums text-fg">{{ $c->total_visits }}</td>
                                <td class="px-4 py-3 text-end font-medium tabular-nums text-primary-600">{{ Money::format($c->total_spent) }}</td>
                                <td class="px-4 py-3 {{ $c->last_visit ? 'text-fg-muted' : 'text-error' }}">{{ $c->last_visit ?? '—' }}</td>
                                <td class="px-4 py-3 text-end">
                                    <div x-data="{ o: false }" @click.outside="o = false" class="relative inline-block">
                                        <button @click="o = !o" class="grid size-8 place-items-center rounded-lg text-fg-subtle hover:bg-surface-3"><x-icon name="more-vertical" class="size-4" /></button>
                                        <div x-show="o" x-cloak class="absolute end-0 z-10 mt-1 w-40 overflow-hidden rounded-lg border border-line bg-surface py-1 shadow-lg">
                                            <a href="{{ route('customers.detail', $c->uuid) }}" wire:navigate class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-fg hover:bg-surface-2"><x-icon name="user-plus" class="size-4" />{{ __('dash.details') }}</a>
                                            <button wire:click="openEdit('{{ $c->uuid }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-fg hover:bg-surface-2"><x-icon name="pencil" class="size-4" />{{ __('common.edit') }}</button>
                                            <button wire:click="confirmDelete('{{ $c->uuid }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-error hover:bg-error-light"><x-icon name="trash-2" class="size-4" />{{ __('common.delete') ?? 'Delete' }}</button>
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
    <x-ui.slide-over wire="showForm" :title="$editingUuid ? __('customers.editClientTitle') : __('customers.addClient')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('customers.fullName')" wire:model="form_name" :error="$errors->first('form_name')" />
                <x-ui.input :label="__('customers.phoneOption')" wire:model="form_phone" dir="ltr" :error="$errors->first('form_phone')" />
                <x-ui.input :label="__('customers.emailOption')" type="email" wire:model="form_email" dir="ltr" :error="$errors->first('form_email')" />
                <x-ui.select :label="__('customers.colGroup')" wire:model="form_group" :options="['Regular' => 'Regular', 'VIP' => 'VIP', 'New' => 'New']" />
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('common.notes') }}</label>
                    <textarea wire:model="form_notes" rows="3" class="w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"></textarea>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('common.save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    {{-- Delete confirmation --}}
    <x-ui.confirm-dialog wire="showDelete" :title="__('common.confirmDelete')" :description="__('common.confirmDeleteDesc')" action="deleteCustomer" :actionLabel="__('common.delete')" />
</div>
