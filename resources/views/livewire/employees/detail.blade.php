@php
    use App\Support\Money;
    use Illuminate\Support\Carbon;
    $e = $this->employee();
    $ov = $this->overview;
    $roleMap = [
        'admin' => __('emp.detail.roleAdmin'),
        'manager' => __('emp.detail.roleManager'),
        'staff' => __('emp.detail.roleStaff'),
    ];
    $tabs = [
        'overview' => __('emp.detail.tabOverview'),
        'schedule' => __('emp.detail.tabSchedule'),
        'performance' => __('emp.detail.tabPerformance'),
        'services' => __('emp.detail.tabServices'),
        'activity' => __('emp.detail.tabActivity'),
    ];
@endphp

<div class="mx-auto max-w-5xl p-4 sm:p-6">
    <a href="{{ route('employees') }}" wire:navigate class="mb-4 inline-flex items-center gap-1.5 text-sm text-fg-muted hover:text-fg">
        <x-icon name="chevron-left" class="size-4 rtl:rotate-180" />{{ __('sidebar.employees') }}
    </a>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    {{-- Profile header --}}
    <x-ui.card class="mb-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-4">
                <x-ui.avatar :name="$e->name" class="size-16 text-xl" />
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-xl font-semibold text-fg">{{ $e->name }}</h1>
                        @if ($e->blocked)
                            <x-ui.badge color="error">{{ __('common.blocked') }}</x-ui.badge>
                        @elseif ($e->active)
                            <x-ui.badge color="success">{{ __('common.active') }}</x-ui.badge>
                        @else
                            <x-ui.badge color="neutral">{{ __('common.inactive') }}</x-ui.badge>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-fg-muted">
                        {{ $e->position ?: '—' }}
                        @if ($e->branchName())<span class="text-fg-subtle"> · </span><span class="inline-flex items-center gap-1"><x-icon name="building-2" class="size-3.5" />{{ $e->branchName() }}</span>@endif
                    </p>
                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-fg-muted">
                        <span class="flex items-center gap-1.5" dir="ltr"><x-icon name="phone" class="size-3.5" />{{ $e->phone ?: '—' }}</span>
                        @if ($e->email)<span class="flex items-center gap-1.5" dir="ltr"><x-icon name="mail" class="size-3.5" />{{ $e->email }}</span>@endif
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if ($e->phone)
                    <x-ui.button href="tel:{{ $e->phone }}" variant="secondary" icon="phone">{{ __('common.call') }}</x-ui.button>
                @endif
                <x-ui.button wire:click="openEdit" icon="pencil">{{ __('emp.detail.editProfile') }}</x-ui.button>
            </div>
        </div>

        {{-- KPI strip --}}
        <div class="mt-5 grid grid-cols-2 gap-4 border-t border-line pt-5 sm:grid-cols-4">
            <div><p class="text-xs text-fg-subtle">{{ __('emp.detail.kpiBookings') }}</p><p class="text-lg font-semibold tabular-nums text-fg">{{ number_format($ov['bookings']) }}</p></div>
            <div><p class="text-xs text-fg-subtle">{{ __('emp.detail.kpiRevenue') }}</p><p class="text-lg font-semibold tabular-nums text-primary-600">{{ Money::format($ov['revenue']) }}</p></div>
            <div>
                <p class="text-xs text-fg-subtle">{{ __('emp.detail.kpiRating') }}</p>
                <p class="flex items-center gap-1 text-lg font-semibold tabular-nums text-fg"><x-icon name="star" class="size-4 fill-current text-warning" />{{ number_format($ov['rating'], 1) }}</p>
            </div>
            <div><p class="text-xs text-fg-subtle">{{ __('emp.detail.kpiClients') }}</p><p class="text-lg font-semibold tabular-nums text-fg">{{ number_format($ov['clients']) }}</p></div>
        </div>
    </x-ui.card>

    {{-- Tabs --}}
    <div class="mb-4 flex gap-1 overflow-x-auto border-b border-line">
        @foreach ($tabs as $key => $label)
            <button wire:click="$set('tab', '{{ $key }}')" class="relative whitespace-nowrap px-4 py-2.5 text-sm font-medium transition-colors {{ $tab === $key ? 'text-primary-600' : 'text-fg-muted hover:text-fg' }}">
                {{ $label }}
                @if ($tab === $key)<span class="absolute inset-x-0 -bottom-px h-0.5 rounded bg-primary-500"></span>@endif
            </button>
        @endforeach
    </div>

    {{-- Overview --}}
    @if ($tab === 'overview')
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <x-ui.card>
                <h2 class="mb-3 font-semibold text-fg">{{ __('emp.detail.contactInfo') }}</h2>
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-fg-subtle">{{ __('emp.detail.lblPhone') }}</dt><dd class="text-fg" dir="ltr">{{ $e->phone ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-fg-subtle">{{ __('emp.detail.lblEmail') }}</dt><dd class="truncate text-fg" dir="ltr">{{ $e->email ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-fg-subtle">{{ __('emp.detail.lblBranch') }}</dt><dd class="text-fg">{{ $e->branchName() ?: '—' }}</dd></div>
                </dl>
            </x-ui.card>
            <x-ui.card>
                <h2 class="mb-3 font-semibold text-fg">{{ __('emp.detail.employment') }}</h2>
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-fg-subtle">{{ __('emp.detail.lblPosition') }}</dt><dd class="text-fg">{{ $e->position ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-fg-subtle">{{ __('emp.detail.lblRole') }}</dt><dd class="text-fg">{{ $roleMap[$e->role] ?? ($e->role ?: '—') }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-fg-subtle">{{ __('emp.detail.lblHiredAt') }}</dt><dd class="text-fg">{{ $e->created_at ? Carbon::parse($e->created_at)->isoFormat('D MMM YYYY') : '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-fg-subtle">{{ __('emp.detail.lblEmpNo') }}</dt><dd class="font-mono text-fg">{{ $e->uuid }}</dd></div>
                </dl>
            </x-ui.card>
        </div>
    @endif

    {{-- Schedule (mock) --}}
    @if ($tab === 'schedule')
        @php $sched = $this->schedule; @endphp
        <div class="mb-5 grid grid-cols-2 gap-4 sm:grid-cols-2">
            <x-ui.kpi-card :label="__('emp.detail.schedDaysPerWeek')" :value="__('emp.availability.summaryDays', ['count' => $sched['daysPerWeek']])" icon="calendar-check" iconClass="bg-primary-50 text-primary-600" />
            <x-ui.kpi-card :label="__('emp.detail.schedHoursPerWeek')" :value="__('emp.availability.summaryHours', ['count' => $sched['hoursPerWeek']])" icon="clock" iconClass="bg-info-light text-info" />
        </div>
        <x-ui.card padding="p-0">
            <div class="border-b border-line px-5 py-3.5"><h2 class="font-semibold text-fg">{{ __('emp.detail.scheduleTitle') }}</h2></div>
            <ul class="divide-y divide-line">
                @foreach ($sched['days'] as $day)
                    <li class="flex items-center justify-between px-5 py-3 text-sm">
                        <span class="font-medium text-fg">{{ __('emp.availability.day'.$day['key']) }}</span>
                        @if ($day['from'])
                            <span class="tabular-nums text-fg-muted" dir="ltr">{{ $day['from'] }} – {{ $day['to'] }}</span>
                        @else
                            <span class="text-fg-subtle">{{ __('emp.detail.schedOff') }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif

    {{-- Performance (mock) --}}
    @if ($tab === 'performance')
        @php
            $perf = $this->performanceMetrics;
            $targetPct = $perf['targetGoal'] > 0 ? (int) round($perf['targetAchieved'] / $perf['targetGoal'] * 100) : 0;
        @endphp
        <div class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ($perf['tiles'] as $tile)
                <x-ui.card>
                    <p class="text-xs text-fg-subtle">{{ $tile['label'] }}</p>
                    <p class="mt-1 text-2xl font-semibold tabular-nums text-fg">{{ $tile['value'] }}</p>
                </x-ui.card>
            @endforeach
        </div>
        <x-ui.card>
            <div class="mb-2 flex items-center justify-between">
                <h2 class="font-semibold text-fg">{{ __('emp.detail.perfTargetProgress') }}</h2>
                <span class="text-sm font-semibold tabular-nums {{ $targetPct >= 80 ? 'text-success' : 'text-fg' }}">{{ $targetPct }}%</span>
            </div>
            <div class="h-2.5 w-full overflow-hidden rounded-full bg-surface-3">
                <div class="h-full rounded-full {{ $targetPct >= 80 ? 'bg-success' : 'bg-primary-500' }}" style="width: {{ min(100, max(0, $targetPct)) }}%"></div>
            </div>
            <p class="mt-2 text-xs text-fg-muted">
                {{ __('emp.detail.perfTargetHint', ['achieved' => Money::format($perf['targetAchieved']), 'goal' => Money::format($perf['targetGoal'])]) }}
            </p>
        </x-ui.card>
    @endif

    {{-- Services & commission (mock) --}}
    @if ($tab === 'services')
        <x-ui.card padding="p-0">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[560px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.detail.colService') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('emp.detail.colPrice') }}</th>
                            <th class="px-4 py-3 text-center font-semibold">{{ __('emp.detail.colCommission') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('emp.detail.colEarned') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->commissionServices as $srv)
                            <tr wire:key="srv-{{ $loop->index }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3 font-medium text-fg">{{ $srv['name'] }}</td>
                                <td class="px-4 py-3 text-end tabular-nums text-fg-muted">{{ Money::format($srv['price']) }}</td>
                                <td class="px-4 py-3 text-center"><x-ui.badge color="primary">{{ $srv['rate'] }}%</x-ui.badge></td>
                                <td class="px-4 py-3 text-end font-semibold tabular-nums text-success">{{ Money::format($srv['earned']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endif

    {{-- Activity (mock) --}}
    @if ($tab === 'activity')
        <x-ui.card padding="p-0">
            <div class="border-b border-line px-5 py-3.5"><h2 class="font-semibold text-fg">{{ __('emp.detail.activityTitle') }}</h2></div>
            <ul class="divide-y divide-line">
                @foreach ($this->activity as $item)
                    <li wire:key="act-{{ $loop->index }}" class="flex items-start gap-3 px-5 py-3.5">
                        <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-full bg-surface-2 text-fg-muted"><x-icon :name="$item['icon']" class="size-4" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-fg">{{ $item['text'] }}</p>
                            <p class="mt-0.5 text-xs text-fg-subtle">{{ Carbon::parse($item['at'])->isoFormat('D MMM، HH:mm') }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif

    {{-- Edit slide-over stub --}}
    <x-ui.slide-over wire="showEdit" :title="__('emp.detail.editTitle')">
        <form wire:submit="saveEdit" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.alert type="info">{{ __('emp.detail.editHint') }}</x-ui.alert>
                <x-ui.input :label="__('emp.detail.fldName')" wire:model="form_name" :error="$errors->first('form_name')" required />
                <x-ui.input :label="__('emp.detail.fldPosition')" wire:model="form_position" :error="$errors->first('form_position')" />
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('emp.detail.fldNote') }}</label>
                    <textarea wire:model="form_note" rows="4" class="w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"></textarea>
                    @error('form_note') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="saveEdit">{{ __('emp.detail.saveChanges') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>
</div>
