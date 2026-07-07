@php
    use App\Support\Money;
    use Illuminate\Support\Carbon;

    $statusMeta = [
        'draft' => ['label' => __('emp.payroll.statusDraft'), 'color' => 'neutral'],
        'approved' => ['label' => __('emp.payroll.statusApproved'), 'color' => 'info'],
        'paid' => ['label' => __('emp.payroll.statusPaid'), 'color' => 'success'],
    ];
    $slip = $this->row($payslipUuid);
@endphp

<div class="p-4 sm:p-6">
    <x-ui.page-header :title="__('emp.payroll.title')" :subtitle="__('emp.payroll.subtitle')">
        @if ($this->canManage())
            <x-slot:actions>
                @if ($this->pendingCount() > 0)
                    <x-ui.button variant="secondary" icon="check-circle-2" wire:click="processAllPending"
                        wire:loading.attr="disabled" wire:target="processAllPending">
                        {{ __('emp.payroll.processPending', ['count' => $this->pendingCount()]) }}
                    </x-ui.button>
                @endif
                <x-ui.button icon="plus" wire:click="openGenerate">{{ __('emp.payroll.generate') }}</x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    @if (! $this->canManage())
        <x-ui.card>
            <x-ui.empty-state :title="__('emp.payroll.noAccess')" :description="__('emp.payroll.noAccessDesc')" icon="shield" />
        </x-ui.card>
    @else
        @if ($this->usingFallback())
            <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
        @endif

        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-ui.kpi-card :label="__('emp.payroll.kpiTotal')" :value="Money::compact($this->kpis['total'])" icon="wallet" iconClass="bg-primary-100 text-primary-600" />
            <x-ui.kpi-card :label="__('emp.payroll.kpiPending')" :value="Money::compact($this->kpis['pending'])" icon="clock" iconClass="bg-warning-light text-warning" />
            <x-ui.kpi-card :label="__('emp.payroll.kpiPaid')" :value="Money::compact($this->kpis['paid'])" icon="check-circle-2" iconClass="bg-success-light text-success" />
        </div>

        <div class="mb-4 flex flex-wrap items-center gap-3">
            <div class="relative min-w-64 flex-1">
                <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-fg-subtle"><x-icon name="search" class="size-4" /></span>
                <input type="search" id="pay-search" aria-label="{{ __('common.search') }}" wire:model.live.debounce.300ms="search" placeholder="{{ __('emp.payroll.searchPlaceholder') }}"
                    class="w-full rounded-lg border border-line bg-surface py-2.5 pe-3.5 ps-10 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
            </div>
            <select wire:model.live="statusFilter" aria-label="{{ __('common.status') }}" class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
                <option value="all">{{ __('common.allStatuses') }}</option>
                @foreach ($statusMeta as $key => $meta)<option value="{{ $key }}">{{ $meta['label'] }}</option>@endforeach
            </select>
        </div>

        <x-ui.card padding="p-0">
            @if ($this->total === 0)
                <x-ui.empty-state :title="__('emp.payroll.empty')" :description="__('emp.payroll.emptyDesc')" icon="receipt">
                    <x-ui.button icon="plus" wire:click="openGenerate">{{ __('emp.payroll.generate') }}</x-ui.button>
                </x-ui.empty-state>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-sm">
                        <thead>
                            <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                                <th class="px-4 py-3 text-start font-semibold">{{ __('emp.payroll.colEmployee') }}</th>
                                <th class="px-4 py-3 text-end font-semibold">{{ __('emp.payroll.colBase') }}</th>
                                <th class="px-4 py-3 text-end font-semibold">{{ __('emp.payroll.colCommissions') }}</th>
                                <th class="px-4 py-3 text-end font-semibold">{{ __('emp.payroll.colDeductions') }}</th>
                                <th class="px-4 py-3 text-end font-semibold">{{ __('emp.payroll.colNet') }}</th>
                                <th class="px-4 py-3 text-start font-semibold">{{ __('common.status') }}</th>
                                <th class="px-4 py-3 text-end font-semibold">{{ __('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->paginated as $r)
                                @php
                                    $status = $r['status'] ?? 'draft';
                                    $meta = $statusMeta[$status] ?? ['label' => $status, 'color' => 'neutral'];
                                @endphp
                                <tr wire:key="pay-{{ $r['uuid'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-fg">{{ $r['employee'] ?? '—' }}</p>
                                        <p class="text-xs text-fg-subtle tabular-nums" dir="ltr">{{ $r['period'] ?? '' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-end tabular-nums text-fg-muted">{{ Money::format((int) ($r['base_salary'] ?? 0)) }}</td>
                                    <td class="px-4 py-3 text-end tabular-nums text-success">+{{ Money::format((int) ($r['commissions'] ?? 0)) }}</td>
                                    <td class="px-4 py-3 text-end tabular-nums text-error">−{{ Money::format((int) ($r['deductions'] ?? 0)) }}</td>
                                    <td class="px-4 py-3 text-end font-semibold tabular-nums text-fg">{{ Money::format((int) ($r['net'] ?? 0)) }}</td>
                                    <td class="px-4 py-3"><x-ui.badge :color="$meta['color']">{{ $meta['label'] }}</x-ui.badge></td>
                                    <td class="px-4 py-3 text-end">
                                        <x-ui.dropdown :ariaLabel="__('common.actions')">
                                            <x-ui.dropdown-item icon="receipt" wire:click="openPayslip('{{ $r['uuid'] }}')">{{ __('emp.payroll.viewPayslip') }}</x-ui.dropdown-item>
                                            @if ($status === 'draft')
                                                <x-ui.dropdown-item icon="check" wire:click="approvePayroll('{{ $r['uuid'] }}')">{{ __('common.approve') }}</x-ui.dropdown-item>
                                            @endif
                                            @if ($status === 'approved')
                                                <x-ui.dropdown-item icon="wallet" wire:click="openPay('{{ $r['uuid'] }}')">{{ __('emp.payroll.pay') }}</x-ui.dropdown-item>
                                            @endif
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
    @endif

    {{-- Generate payroll modal --}}
    <x-ui.modal wire="showGenerate">
        <div class="mb-3 grid size-11 place-items-center rounded-full bg-primary-100 text-primary-600"><x-icon name="calendar-check" class="size-5" /></div>
        <h3 class="text-lg font-semibold text-fg">{{ __('emp.payroll.generateTitle') }}</h3>
        <p class="mt-1 text-sm text-fg-muted">{{ __('emp.payroll.generateDesc') }}</p>
        <form wire:submit="generatePayroll" class="mt-4 space-y-4">
            <x-ui.input type="month" :label="__('emp.payroll.lblPeriod')" wire:model="form_period" required :error="$errors->first('form_period')" />
            <div class="flex items-center justify-end gap-2">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="generatePayroll">{{ __('emp.payroll.generate') }}</x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    {{-- Pay modal --}}
    <x-ui.modal wire="showPay">
        <div class="mb-3 grid size-11 place-items-center rounded-full bg-success-light text-success"><x-icon name="wallet" class="size-5" /></div>
        <h3 class="text-lg font-semibold text-fg">{{ __('emp.payroll.payTitle') }}</h3>
        <p class="mt-1 text-sm text-fg-muted">{{ __('emp.payroll.payDesc') }}</p>
        <form wire:submit="payPayroll" class="mt-4 space-y-4">
            <x-ui.select :label="__('emp.payroll.lblMethod')" wire:model="pay_method" required
                :options="['cash' => __('emp.payroll.methodCash'), 'bank' => __('emp.payroll.methodBank'), 'cheque' => __('emp.payroll.methodCheque')]"
                :error="$errors->first('pay_method')" />
            <x-ui.input type="number" :label="__('emp.payroll.lblAmount')" wire:model="pay_amount" min="0" step="0.01" required :error="$errors->first('pay_amount')" />
            <div>
                <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('emp.payroll.lblNotes') }}</label>
                <textarea wire:model="pay_notes" rows="3" class="w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"></textarea>
                @error('pay_notes') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-center justify-end gap-2">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="payPayroll">{{ __('emp.payroll.confirmPay') }}</x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    {{-- Payslip breakdown modal --}}
    <x-ui.modal wire="showPayslip" maxWidth="max-w-md">
        @if ($slip)
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-fg">{{ __('emp.payroll.payslipTitle') }}</h3>
                    <p class="mt-0.5 text-sm text-fg-muted">{{ $slip['employee'] ?? '—' }} · <span class="tabular-nums" dir="ltr">{{ $slip['period'] ?? '' }}</span></p>
                </div>
                @php $sMeta = $statusMeta[$slip['status'] ?? 'draft'] ?? ['label' => $slip['status'] ?? '', 'color' => 'neutral']; @endphp
                <x-ui.badge :color="$sMeta['color']">{{ $sMeta['label'] }}</x-ui.badge>
            </div>

            <dl class="mt-4 space-y-2.5 text-sm">
                <div class="flex items-center justify-between">
                    <dt class="text-fg-muted">{{ __('emp.payroll.colBase') }}</dt>
                    <dd class="tabular-nums text-fg">{{ Money::format((int) ($slip['base_salary'] ?? 0)) }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-fg-muted">{{ __('emp.payroll.colCommissions') }}</dt>
                    <dd class="tabular-nums text-success">+{{ Money::format((int) ($slip['commissions'] ?? 0)) }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-fg-muted">{{ __('emp.payroll.colDeductions') }}</dt>
                    <dd class="tabular-nums text-error">−{{ Money::format((int) ($slip['deductions'] ?? 0)) }}</dd>
                </div>
                <div class="flex items-center justify-between border-t border-line pt-2.5">
                    <dt class="font-semibold text-fg">{{ __('emp.payroll.colNet') }}</dt>
                    <dd class="text-base font-semibold tabular-nums text-primary-600">{{ Money::format((int) ($slip['net'] ?? 0)) }}</dd>
                </div>
            </dl>

            <div class="mt-5 flex items-center justify-end">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.close') }}</x-ui.button>
            </div>
        @endif
    </x-ui.modal>
</div>
