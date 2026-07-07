@php
    use App\Support\Money;
    use Illuminate\Support\Carbon;
    $typeMeta = [
        'cash_refund' => ['label' => __('ret.typeCashRefund'), 'color' => 'info'],
        'cancel_down_payment' => ['label' => __('ret.typeCancelDown'), 'color' => 'warning'],
        'petty_cash_refund' => ['label' => __('ret.typePettyRefund'), 'color' => 'neutral'],
    ];
@endphp

<div class="p-4 sm:p-6">
    <x-ui.page-header :title="__('sidebar.returns')" :subtitle="__('ret.subtitle')">
        <x-slot:actions>
            <div x-data="{ o: false }" @click.outside="o = false" class="relative">
                <x-ui.button icon="plus" x-on:click="o = ! o">{{ __('ret.newRefund') }}</x-ui.button>
                <div x-show="o" x-cloak class="absolute end-0 z-20 mt-1 w-60 overflow-hidden rounded-lg border border-line bg-surface py-1 shadow-lg">
                    <a href="{{ route('returns.cash-refund') }}" wire:navigate @click="o = false" class="block px-3.5 py-2 text-sm text-fg hover:bg-surface-2">{{ __('ret.typeCashRefund') }}</a>
                    <a href="{{ route('returns.cancel-down-payment') }}" wire:navigate @click="o = false" class="block px-3.5 py-2 text-sm text-fg hover:bg-surface-2">{{ __('ret.typeCancelDown') }}</a>
                    <a href="{{ route('returns.petty-cash-refund') }}" wire:navigate @click="o = false" class="block px-3.5 py-2 text-sm text-fg hover:bg-surface-2">{{ __('ret.typePettyRefund') }}</a>
                </div>
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.kpi-card :label="__('ret.pendingCount')" :value="$this->kpis['pending']" icon="clock" iconClass="bg-warning-light text-warning" />
        <x-ui.kpi-card :label="__('ret.approvedCount')" :value="$this->kpis['approved']" icon="check-circle-2" iconClass="bg-success-light text-success" />
        <x-ui.kpi-card :label="__('ret.rejectedCount')" :value="$this->kpis['rejected']" icon="ban" iconClass="bg-error-light text-error" />
        <x-ui.kpi-card :label="__('ret.totalAmount')" :value="Money::compact($this->kpis['amount'])" icon="wallet" iconClass="bg-primary-100 text-primary-600" />
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <select wire:model.live="typeFilter" aria-label="{{ __('ret.typeAll') }}" class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
            <option value="all">{{ __('ret.typeAll') }}</option>
            @foreach ($typeMeta as $key => $meta)<option value="{{ $key }}">{{ $meta['label'] }}</option>@endforeach
        </select>
        <select wire:model.live="statusFilter" aria-label="{{ __('common.status') }}" class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
            <option value="all">{{ __('common.allStatuses') }}</option>
            <option value="pending">{{ __('ret.pendingCount') }}</option>
            <option value="approved">{{ __('ret.approvedCount') }}</option>
            <option value="rejected">{{ __('ret.rejectedCount') }}</option>
        </select>
    </div>

    <x-ui.card padding="p-0">
        @if (count($this->filtered) === 0)
            <x-ui.empty-state :title="__('common.noData')" icon="rotate-ccw" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('ret.colType') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ $provider->terminology()['customer'] }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('ret.colReason') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('txn.thAmount') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('common.status') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->filtered as $r)
                            @php $meta = $typeMeta[$r->type] ?? ['label' => $r->type, 'color' => 'neutral']; @endphp
                            <tr wire:key="ret-{{ $r->uuid }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3"><x-ui.badge :color="$meta['color']">{{ $meta['label'] }}</x-ui.badge></td>
                                <td class="px-4 py-3 text-fg">{{ $r->customerName() ?: '—' }}</td>
                                <td class="max-w-xs px-4 py-3 text-fg-muted"><span class="line-clamp-1">{{ $r->reason }}</span></td>
                                <td class="px-4 py-3 text-end font-medium tabular-nums text-fg">{{ Money::format($r->amount) }}</td>
                                <td class="px-4 py-3"><x-ui.status-pill :status="$r->status === 'approved' ? 'completed' : ($r->status === 'rejected' ? 'cancelled' : 'pending')" :label="match($r->status) { 'approved' => __('ret.approvedCount'), 'rejected' => __('ret.rejectedCount'), default => __('ret.pendingCount') }" /></td>
                                <td class="px-4 py-3 text-end">
                                    @if ($r->status === 'pending')
                                        <div class="flex items-center justify-end gap-2">
                                            <x-ui.button variant="ghost" size="sm" wire:click="approve('{{ $r->uuid }}')" icon="check">{{ __('common.approve') }}</x-ui.button>
                                            <x-ui.button variant="ghost" size="sm" wire:click="openReject('{{ $r->uuid }}')" icon="ban">{{ __('common.reject') }}</x-ui.button>
                                        </div>
                                    @else
                                        <span class="text-xs text-fg-subtle">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    {{-- Reject modal --}}
    <x-ui.modal wire="showReject">
        <div class="mb-3 grid size-11 place-items-center rounded-full bg-error-light text-error"><x-icon name="ban" class="size-5" /></div>
        <h3 class="text-lg font-semibold text-fg">{{ __('common.reject') }}</h3>
        <form wire:submit="submitReject" class="mt-3 space-y-3">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('ret.rejectReason') }}</label>
                <textarea wire:model="rejectReason" rows="3" class="w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"></textarea>
                @error('rejectReason') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-center justify-end gap-2">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" variant="destructive" wire:loading.attr="disabled" wire:target="submitReject">{{ __('common.reject') }}</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
