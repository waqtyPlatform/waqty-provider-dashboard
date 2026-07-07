@php use App\Support\Money; @endphp

<div class="p-4 sm:p-6">
    <x-ui.page-header :title="__('marketing.title')" :subtitle="__('marketing.desc')">
        <x-slot:actions>
            <x-ui.button icon="plus" :href="route('marketing.campaigns')" wire:navigate>{{ __('marketing.newCampaign') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.kpi-card :label="__('marketing.activeOffers')" :value="number_format($this->kpis['activeOffers'])" icon="megaphone" />
        <x-ui.kpi-card :label="__('marketing.redemptions')" :value="number_format($this->kpis['redemptions'])" icon="receipt" iconClass="bg-info-light text-info" />
        <x-ui.kpi-card :label="__('mkt.hub.kpiMessagesSent')" :value="number_format($this->kpis['messagesSent'])" icon="mail" iconClass="bg-purple-100 text-purple-600" />
        <x-ui.kpi-card :label="__('mkt.hub.kpiReach')" :value="number_format($this->kpis['reach'])" icon="users" iconClass="bg-success-light text-success" />
    </div>

    {{-- Channel tabs --}}
    @php $tabs = ['offers' => __('marketing.tabOffers'), 'promos' => __('marketing.tabPromos'), 'messages' => __('marketing.tabMsg'), 'campaigns' => __('marketing.tabCamp')]; @endphp
    <div class="mb-4 inline-flex flex-wrap rounded-lg border border-line bg-surface p-0.5">
        @foreach ($tabs as $key => $lbl)
            <button wire:click="$set('tab', '{{ $key }}')" class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors {{ $tab === $key ? 'bg-primary-500 text-white' : 'text-fg-muted hover:text-fg' }}">{{ $lbl }}</button>
        @endforeach
    </div>

    <x-ui.card padding="p-0">
        @if (count($this->preview) === 0)
            <div class="p-6">
                <x-ui.empty-state :title="__('mkt.hub.emptyTitle')" :description="__('mkt.hub.emptyDesc')" icon="megaphone">
                    <x-ui.button icon="plus" :href="route('marketing.campaigns')" wire:navigate>{{ __('marketing.createFirstCamp') }}</x-ui.button>
                </x-ui.empty-state>
            </div>
        @else
            <ul class="divide-y divide-line">
                @foreach ($this->preview as $item)
                    <li wire:key="hub-{{ $tab }}-{{ $item['id'] }}" class="flex items-center justify-between gap-3 px-5 py-3.5">
                        @if ($tab === 'offers')
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-primary-50 text-primary-600"><x-icon name="megaphone" class="size-4" /></span>
                                <span class="truncate font-medium text-fg">{{ $item['name'] }}</span>
                            </div>
                            <span class="shrink-0 text-sm font-semibold text-primary-600">{{ $item['type'] === 'percentage' ? $item['value'].'% '.__('mkt.lblOFF') : Money::format((int) $item['value']) }}</span>
                        @elseif ($tab === 'promos')
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-info-light text-info"><x-icon name="receipt" class="size-4" /></span>
                                <span class="truncate font-mono font-semibold text-fg">{{ $item['code'] }}</span>
                            </div>
                            <span class="shrink-0 text-sm text-fg-muted">{{ number_format((int) $item['used']) }} {{ __('mkt.lblUsed') }}</span>
                        @elseif ($tab === 'messages')
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-purple-100 text-purple-600"><x-icon name="mail" class="size-4" /></span>
                                <span class="truncate font-medium text-fg">{{ $item['name'] }}</span>
                            </div>
                            <x-ui.badge color="neutral">{{ __('mkt.messages.ch'.ucfirst($item['channel'])) }}</x-ui.badge>
                        @else
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-success-light text-success"><x-icon name="bell" class="size-4" /></span>
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-fg">{{ $item['name'] }}</p>
                                    <p class="text-xs text-fg-subtle">{{ __('mkt.messages.ch'.ucfirst($item['channel'])) }}</p>
                                </div>
                            </div>
                            <x-ui.status-pill :status="$item['status']" :label="__('mkt.campaigns.st'.ucfirst($item['status']))" />
                        @endif
                    </li>
                @endforeach
            </ul>

            <div class="border-t border-line px-5 py-3">
                <a href="{{ route($this->viewAllRoute()) }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:text-primary-700">
                    {{ __('mkt.hub.viewAll') }}<x-icon name="chevron-left" class="size-4" />
                </a>
            </div>
        @endif
    </x-ui.card>
</div>
