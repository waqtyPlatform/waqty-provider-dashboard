<div class="mx-auto max-w-5xl p-6">
    <x-ui.page-header :title="__('settings.subscription.title')" :subtitle="__('settings.subscription.desc')" />

    {{-- Current plan summary + usage meters --}}
    @php $current = $this->current; @endphp
    <x-ui.card class="mb-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <x-ui.badge color="primary">{{ __('settings.subscription.currentPlanLabel') }}</x-ui.badge>
                <div class="mt-2 flex items-baseline gap-1.5">
                    <span class="text-2xl font-bold text-fg">{{ $current['name'] }}</span>
                    <span class="text-lg font-semibold text-primary-600">{{ $current['price'] }}</span>
                    <span class="text-sm text-fg-subtle">{{ __('settings.subscription.mo') }}</span>
                </div>
                <p class="mt-1 text-sm text-fg-muted">{{ __('settings.subscription.renewsOn') }}</p>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-3">
            @foreach ($usage as $u)
                @php $pct = $u['total'] > 0 ? min(100, (int) round($u['used'] / $u['total'] * 100)) : 0; @endphp
                <div>
                    <div class="mb-1.5 flex items-center justify-between text-sm">
                        <span class="font-medium text-fg">{{ __($u['labelKey']) }}</span>
                        <span class="tabular-nums text-fg-muted">{{ $u['used'] }} / {{ $u['total'] }}</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-surface-3">
                        <div class="h-full rounded-full bg-primary-500" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-ui.card>

    {{-- Plan catalogue --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        @foreach ($plans as $plan)
            @php $isCurrent = $plan['key'] === $currentKey; @endphp
            <div wire:key="plan-{{ $plan['key'] }}"
                class="flex flex-col rounded-xl border bg-surface p-5 shadow-xs {{ $isCurrent ? 'border-primary-500 ring-1 ring-primary-500' : 'border-line' }}">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="font-semibold text-fg">{{ $plan['name'] }}</h3>
                    @if ($isCurrent)
                        <x-ui.badge color="primary">{{ __('settings.subscription.currentPlanLabel') }}</x-ui.badge>
                    @endif
                </div>
                <div class="mt-2 flex items-baseline gap-1.5">
                    <span class="text-3xl font-bold text-fg">{{ $plan['price'] }}</span>
                    <span class="text-sm text-fg-subtle">{{ __('settings.subscription.mo') }}</span>
                </div>
                <p class="mt-1.5 text-sm text-fg-muted">{{ __($plan['descKey']) }}</p>
                <ul class="mt-4 flex-1 space-y-2 border-t border-line pt-4 text-sm text-fg-muted">
                    @foreach ($plan['features'] as $feature)
                        <li class="flex items-center gap-2">
                            <x-icon name="check" class="size-4 shrink-0 text-success" />
                            <span>{{ __($feature) }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-5">
                    @if ($isCurrent)
                        <x-ui.button variant="secondary" class="w-full" disabled>{{ __('settings.subscription.current') }}</x-ui.button>
                    @else
                        <x-ui.button variant="primary" class="w-full" wire:click="upgrade('{{ $plan['key'] }}')">{{ __('settings.subscription.upgrade') }}</x-ui.button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
