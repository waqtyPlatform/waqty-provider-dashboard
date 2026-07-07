@php
    use App\Support\Money;
    use Illuminate\Support\Carbon;

    $statusLabels = [
        'pending' => __('payments.statusPending'),
        'completed' => __('payments.statusCompleted'),
        'failed' => __('payments.statusFailed'),
        'refunded' => __('payments.statusRefunded'),
    ];
    $methodLabels = [
        'cash' => __('payments.methodCash'),
        'paymob' => __('payments.methodPaymob'),
    ];
@endphp

<div class="p-4 sm:p-6">
    <x-ui.page-header :title="__('payments.title')" :subtitle="__('payments.subtitle')">
        <x-slot:actions>
            <x-ui.button icon="wallet" wire:click="openCreate">{{ __('payments.recordPayment') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    {{-- KPIs --}}
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.kpi-card :label="__('payments.kpiCollected')" :value="Money::compact($this->kpis['collected'])" icon="wallet" iconClass="bg-success-light text-success" />
        <x-ui.kpi-card :label="__('payments.kpiPending')" :value="$this->kpis['pending']" icon="clock" iconClass="bg-warning-light text-warning" />
        <x-ui.kpi-card :label="__('payments.kpiRefunded')" :value="Money::compact($this->kpis['refunded'])" icon="rotate-ccw" iconClass="bg-error-light text-error" />
        <x-ui.kpi-card :label="__('payments.kpiTotalRecords')" :value="$this->kpis['records']" icon="receipt" />
    </div>

    {{-- Controls --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="relative min-w-64 flex-1">
            <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-fg-subtle"><x-icon name="search" class="size-4" /></span>
            <input type="search" id="payments-search" aria-label="{{ __('common.search') }}" wire:model.live.debounce.300ms="search" placeholder="{{ __('payments.searchPlaceholder') }}"
                class="w-full rounded-lg border border-line bg-surface py-2.5 pe-3.5 ps-10 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
        </div>
        <select wire:model.live="methodFilter" id="payments-method" aria-label="{{ __('payments.colMethod') }}" class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
            <option value="all">{{ __('payments.allMethods') }}</option>
            <option value="cash">{{ __('payments.methodCash') }}</option>
            <option value="paymob">{{ __('payments.methodPaymob') }}</option>
        </select>
        <select wire:model.live="statusFilter" id="payments-status" aria-label="{{ __('common.status') }}" class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
            <option value="all">{{ __('payments.allStatuses') }}</option>
            <option value="pending">{{ __('payments.statusPending') }}</option>
            <option value="completed">{{ __('payments.statusCompleted') }}</option>
            <option value="failed">{{ __('payments.statusFailed') }}</option>
            <option value="refunded">{{ __('payments.statusRefunded') }}</option>
        </select>
    </div>

    {{-- Table --}}
    <x-ui.card padding="p-0">
        @if ($this->total === 0)
            <x-ui.empty-state :title="__('payments.emptyTitle')" :description="__('payments.emptyDesc')" icon="wallet" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[880px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-start text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('payments.colBooking') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('payments.colMethod') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('payments.colAmount') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('common.status') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('payments.colTransactionId') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('payments.colDate') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->paginated as $p)
                            <tr wire:key="pmt-{{ $p->uuid }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-fg">{{ $p->serviceName() }}</div>
                                    <div class="text-xs text-fg-subtle">{{ $p->bookingDate() ? Carbon::parse($p->bookingDate())->isoFormat('D MMM YYYY') : '—' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <x-ui.badge :color="$p->payment_method === 'paymob' ? 'purple' : 'neutral'">{{ $methodLabels[$p->payment_method] ?? $p->payment_method }}</x-ui.badge>
                                </td>
                                <td class="px-4 py-3 text-end font-medium tabular-nums text-success">{{ $p->amount !== null ? Money::format($p->amount) : '—' }}</td>
                                <td class="px-4 py-3"><x-ui.status-pill :status="$p->status" :label="$statusLabels[$p->status] ?? $p->status" /></td>
                                <td class="px-4 py-3 font-mono text-xs text-fg-muted" dir="ltr">{{ $p->transaction_id ?: '—' }}</td>
                                <td class="px-4 py-3 text-fg-muted">{{ $p->created_at ? Carbon::parse($p->created_at)->isoFormat('D MMM YYYY') : '—' }}</td>
                                <td class="px-4 py-3 text-end">
                                    <x-ui.dropdown>
                                        <x-ui.dropdown-item icon="pencil" wire:click="openEdit('{{ $p->uuid }}')">{{ __('common.edit') }}</x-ui.dropdown-item>
                                        @if ($p->status !== 'completed')
                                            <x-ui.dropdown-item icon="check-circle-2" wire:click="complete('{{ $p->uuid }}')">{{ __('payments.actionComplete') }}</x-ui.dropdown-item>
                                        @endif
                                        @if ($p->status !== 'refunded')
                                            <x-ui.dropdown-item icon="rotate-ccw" wire:click="refund('{{ $p->uuid }}')">{{ __('payments.actionRefund') }}</x-ui.dropdown-item>
                                        @endif
                                        <x-ui.dropdown-item icon="trash-2" wire:click="confirmDelete('{{ $p->uuid }}')" destructive>{{ __('common.delete') }}</x-ui.dropdown-item>
                                    </x-ui.dropdown>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-ui.pagination :page="$currentPage" :perPage="$perPage" :total="$this->total" field="currentPage" />
        @endif
    </x-ui.card>

    {{-- Record / edit slide-over --}}
    <x-ui.slide-over wire="showForm" :title="$editingUuid ? __('payments.editTitle') : __('payments.recordPayment')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('payments.lblBookingUuid')" wire:model="form_booking_uuid" dir="ltr" :placeholder="__('payments.phBookingUuid')" :error="$errors->first('form_booking_uuid')" :disabled="(bool) $editingUuid" :required="true" />
                <x-ui.input :label="__('payments.lblAmount')" type="number" step="0.01" min="0" wire:model="form_amount" dir="ltr" :error="$errors->first('form_amount')" :required="true" />
                <x-ui.select :label="__('payments.lblPaymentMethod')" wire:model="form_payment_method" :options="['cash' => __('payments.methodCash'), 'paymob' => __('payments.methodPaymob')]" :error="$errors->first('form_payment_method')" :required="true" />
                <x-ui.select :label="__('common.status')" wire:model="form_status" :options="['pending' => __('payments.statusPending'), 'completed' => __('payments.statusCompleted'), 'failed' => __('payments.statusFailed'), 'refunded' => __('payments.statusRefunded')]" :error="$errors->first('form_status')" :required="true" />
                <x-ui.input :label="__('payments.colTransactionId')" wire:model="form_transaction_id" dir="ltr" :placeholder="__('payments.phTransactionId')" :error="$errors->first('form_transaction_id')" />
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('common.notes') }}</label>
                    <textarea wire:model="form_notes" rows="3" placeholder="{{ __('payments.phNotes') }}" class="w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"></textarea>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" loadingTarget="save">{{ $editingUuid ? __('payments.saveChanges') : __('payments.record') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    {{-- Delete confirmation --}}
    <x-ui.confirm-dialog wire="showDelete" :title="__('common.confirmDelete')" :description="__('common.confirmDeleteDesc')" action="deletePayment" :actionLabel="__('common.delete')" />
</div>
