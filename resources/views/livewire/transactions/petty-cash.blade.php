@php use App\Support\Money; use Illuminate\Support\Carbon; @endphp

<div class="p-4 sm:p-6">
    <x-transactions.tabs :active="'petty-cash'" />

    <x-ui.page-header :title="__('txn.pettycash.title')" :subtitle="__('txn.pettycash.subtitle')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('txn.pettycash.add') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi-card :label="__('txn.pettycash.kpiSpent')" :value="Money::compact($this->kpis['spent'])" icon="wallet" iconClass="bg-error-light text-error" />
        <x-ui.kpi-card :label="__('txn.pettycash.kpiPending')" :value="$this->kpis['pending']" icon="clock" iconClass="bg-warning-light text-warning" />
        <x-ui.kpi-card :label="__('txn.pettycash.kpiThisMonth')" :value="Money::compact($this->kpis['thisMonth'])" icon="calendar" iconClass="bg-primary-100 text-primary-600" />
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="relative min-w-64 flex-1">
            <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-fg-subtle"><x-icon name="search" class="size-4" /></span>
            <input type="search" id="pc-search" aria-label="{{ __('common.search') }}" wire:model.live.debounce.300ms="search" placeholder="{{ __('txn.pettycash.searchPlaceholder') }}"
                class="w-full rounded-lg border border-line bg-surface py-2.5 pe-3.5 ps-10 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
        </div>
        <select wire:model.live="statusFilter" aria-label="{{ __('common.status') }}" class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
            <option value="all">{{ __('common.allStatuses') }}</option>
            <option value="pending">{{ __('txn.pettycash.statusPending') }}</option>
            <option value="approved">{{ __('txn.pettycash.statusApproved') }}</option>
            <option value="rejected">{{ __('txn.pettycash.statusRejected') }}</option>
        </select>
    </div>

    <x-ui.card padding="p-0">
        @if ($this->total === 0)
            <x-ui.empty-state :title="__('txn.pettycash.empty')" :description="__('txn.pettycash.emptyDesc')" icon="wallet" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.pettycash.colReference') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.pettycash.colCategory') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.pettycash.colDescription') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('txn.pettycash.colAmount') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('txn.pettycash.colRequestedBy') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('common.status') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->paginated as $r)
                            @php $status = $r['status'] ?? 'pending'; @endphp
                            <tr wire:key="pc-{{ $r['uuid'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3 font-mono text-xs text-fg-muted" dir="ltr">{{ $r['reference'] ?? '—' }}</td>
                                <td class="px-4 py-3"><x-ui.badge color="neutral">{{ $r['category'] ?? '—' }}</x-ui.badge></td>
                                <td class="max-w-xs px-4 py-3 font-medium text-fg"><span class="line-clamp-1">{{ $r['description'] ?? '—' }}</span></td>
                                <td class="px-4 py-3 text-end font-medium tabular-nums text-fg">{{ Money::format((int) ($r['amount'] ?? 0)) }}</td>
                                <td class="px-4 py-3 text-fg-muted">{{ $r['requested_by'] ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <x-ui.status-pill
                                        :status="$status === 'approved' ? 'completed' : ($status === 'rejected' ? 'cancelled' : 'pending')"
                                        :label="match($status) { 'approved' => __('txn.pettycash.statusApproved'), 'rejected' => __('txn.pettycash.statusRejected'), default => __('txn.pettycash.statusPending') }" />
                                </td>
                                <td class="px-4 py-3 text-end">
                                    @if ($status === 'pending')
                                        <x-ui.dropdown :ariaLabel="__('common.actions')">
                                            <x-ui.dropdown-item icon="check" wire:click="approve('{{ $r['uuid'] }}')">{{ __('common.approve') }}</x-ui.dropdown-item>
                                            <x-ui.dropdown-item icon="ban" destructive wire:click="openReject('{{ $r['uuid'] }}')">{{ __('common.reject') }}</x-ui.dropdown-item>
                                        </x-ui.dropdown>
                                    @else
                                        <span class="text-xs text-fg-subtle">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-ui.pagination :page="$currentPage" :perPage="$perPage" :total="$this->total" field="currentPage" />
        @endif
    </x-ui.card>

    {{-- New petty-cash slide-over --}}
    <x-ui.slide-over wire="showForm" :title="__('txn.pettycash.addTitle')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.select :label="__('txn.pettycash.lblCategory')" wire:model="form_category" required
                    :placeholder="__('txn.pettycash.categoryPlaceholder')"
                    :options="collect($this->categories())->mapWithKeys(fn ($c) => [$c => $c])->toArray()"
                    :error="$errors->first('form_category')" />
                <x-ui.input type="number" :label="__('txn.pettycash.lblAmount')" wire:model="form_amount" min="0" step="0.01" required :error="$errors->first('form_amount')" />
                <x-ui.input :label="__('txn.pettycash.lblDescription')" wire:model="form_description" required :error="$errors->first('form_description')" />
                <x-ui.input :label="__('txn.pettycash.lblApprover')" wire:model="form_approver" :error="$errors->first('form_approver')" />
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('common.save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    {{-- Reject confirm-dialog (captures reason) --}}
    <x-ui.confirm-dialog wire="showReject" :title="__('txn.pettycash.rejectTitle')" :description="__('txn.pettycash.rejectDesc')"
        action="submitReject" :actionLabel="__('common.reject')" icon="ban">
        <div class="mt-3 text-start">
            <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('txn.pettycash.reasonLabel') }}</label>
            <textarea wire:model="rejectReason" rows="3" class="w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"></textarea>
            @error('rejectReason') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
        </div>
    </x-ui.confirm-dialog>
</div>
