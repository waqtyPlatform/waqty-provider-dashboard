<div class="p-4 sm:p-6">
    <x-ui.page-header :title="__('emp.branchManagement.title')" :subtitle="__('emp.branchManagement.subtitle')" />

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    {{-- KPIs --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi-card :label="__('emp.branchManagement.kpiBranches')" :value="$this->kpis['branches']" icon="building-2" iconClass="bg-primary-100 text-primary-600" />
        <x-ui.kpi-card :label="__('emp.branchManagement.kpiStaff')" :value="$this->kpis['staff']" icon="users" iconClass="bg-info-light text-info" />
        <x-ui.kpi-card :label="__('emp.branchManagement.kpiAvg')" :value="$this->kpis['avg']" icon="gauge" iconClass="bg-success-light text-success" />
    </div>

    {{-- Branch rosters --}}
    @if (count($this->branches) === 0)
        <x-ui.empty-state :title="__('emp.branchManagement.emptyTitle')" :description="__('emp.branchManagement.emptyDesc')" icon="building-2" />
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($this->branches as $branch)
                <x-ui.card wire:key="branch-{{ $branch['uuid'] }}" class="flex flex-col">
                    <div class="flex items-start justify-between gap-3 border-b border-line pb-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-primary-50 text-primary-600">
                                <x-icon name="building-2" class="size-5" />
                            </span>
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-fg">{{ $branch['name'] }}</p>
                                @if ($branch['area'])
                                    <p class="truncate text-xs text-fg-subtle">{{ $branch['area'] }}</p>
                                @endif
                            </div>
                        </div>
                        <x-ui.badge color="primary">
                            <x-icon name="users" class="size-3" />
                            {{ $branch['headcount'] }} {{ __('emp.branchManagement.staffUnit') }}
                        </x-ui.badge>
                    </div>

                    @if ($branch['headcount'] === 0)
                        <p class="py-8 text-center text-sm text-fg-subtle">{{ __('emp.branchManagement.noStaff') }}</p>
                    @else
                        <ul class="mt-4 space-y-3">
                            @foreach ($branch['staff'] as $i => $member)
                                <li wire:key="branch-{{ $branch['uuid'] }}-staff-{{ $i }}" class="flex items-center gap-3">
                                    <x-ui.avatar :name="$member['name']" />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-fg">{{ $member['name'] ?: '—' }}</p>
                                        <p class="truncate text-xs text-fg-subtle">{{ $member['position'] ?: '—' }}</p>
                                    </div>
                                    <span class="flex shrink-0 items-center gap-1.5 text-xs {{ $member['active'] ? 'text-success' : 'text-fg-subtle' }}">
                                        <span class="size-2 rounded-full {{ $member['active'] ? 'bg-success' : 'bg-fg-subtle/40' }}"></span>
                                        {{ $member['active'] ? __('common.active') : __('common.inactive') }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-ui.card>
            @endforeach
        </div>
    @endif
</div>
