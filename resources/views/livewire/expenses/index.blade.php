@php use App\Support\Money; use Illuminate\Support\Carbon; @endphp

<div class="p-6">
    <x-ui.page-header :title="__('expenses.title')" :subtitle="__('expenses.desc')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('expenses.add') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi-card :label="__('expenses.total')" :value="Money::compact($this->kpis['total'])" icon="wallet" iconClass="bg-error-light text-error" />
        <x-ui.kpi-card :label="__('expenses.pending')" :value="$this->kpis['pending']" icon="clock" iconClass="bg-warning-light text-warning" />
        <x-ui.kpi-card :label="__('expenses.topCategory')" :value="$this->kpis['topCategory']" icon="tag" iconClass="bg-info-light text-info" />
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="relative min-w-64 flex-1">
            <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-fg-subtle"><x-icon name="search" class="size-4" /></span>
            <input type="search" id="exp-search" aria-label="{{ __('common.search') }}" wire:model.live.debounce.300ms="search" placeholder="{{ __('expenses.search') }}"
                class="w-full rounded-lg border border-line bg-surface py-2.5 pe-3.5 ps-10 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
        </div>
        <select wire:model.live="categoryFilter" aria-label="{{ __('exp.catAll') }}" class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
            <option value="all">{{ __('exp.catAll') }}</option>
            @foreach ($this->categories() as $cat)<option value="{{ $cat }}">{{ __('exp.cat.'.$cat) }}</option>@endforeach
        </select>
        <select wire:model.live="statusFilter" aria-label="{{ __('exp.thStatus') }}" class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
            <option value="all">{{ __('common.all') }}</option>
            <option value="pending">{{ __('expenses.statusPending') }}</option>
            <option value="approved">{{ __('expenses.statusApproved') }}</option>
            <option value="rejected">{{ __('expenses.statusRejected') }}</option>
        </select>
    </div>

    <x-ui.card padding="p-0">
        @if ($this->total === 0)
            <x-ui.empty-state :title="__('expenses.noExpensesFound')" :description="__('expenses.emptyNewWorkspaceDesc')" icon="wallet" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('exp.thDate') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('exp.thDesc') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('exp.thCategory') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('exp.thVendor') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('exp.thAmount') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('exp.thStatus') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->paginated as $e)
                            <tr wire:key="exp-{{ $e->uuid }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3 text-fg-muted">{{ $e->date ? Carbon::parse($e->date)->isoFormat('D MMM') : '—' }}</td>
                                <td class="px-4 py-3 font-medium text-fg">{{ $e->description }}</td>
                                <td class="px-4 py-3"><x-ui.badge color="neutral">{{ $e->category ? __('exp.cat.'.$e->category) : '—' }}</x-ui.badge></td>
                                <td class="px-4 py-3 text-fg-muted">{{ $e->vendor ?: '—' }}</td>
                                <td class="px-4 py-3 text-end font-medium tabular-nums text-fg">{{ Money::format($e->amount) }}</td>
                                <td class="px-4 py-3"><x-ui.status-pill :status="$e->status === 'approved' ? 'completed' : ($e->status === 'rejected' ? 'cancelled' : 'pending')" :label="match($e->status) { 'approved' => __('expenses.statusApproved'), 'rejected' => __('expenses.statusRejected'), default => __('expenses.statusPending') }" /></td>
                                <td class="px-4 py-3 text-end">
                                    <div x-data="{ o: false }" @click.outside="o = false" class="relative inline-block">
                                        <button @click="o = !o" class="grid size-8 place-items-center rounded-lg text-fg-subtle hover:bg-surface-3"><x-icon name="more-vertical" class="size-4" /></button>
                                        <div x-show="o" x-cloak class="absolute end-0 z-10 mt-1 w-40 overflow-hidden rounded-lg border border-line bg-surface py-1 shadow-lg">
                                            @if ($e->status === 'pending')
                                                <button wire:click="approve('{{ $e->uuid }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-success hover:bg-success-light"><x-icon name="check" class="size-4" />{{ __('common.approve') }}</button>
                                                <button wire:click="reject('{{ $e->uuid }}')" @click="o=false" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-fg hover:bg-surface-2"><x-icon name="ban" class="size-4" />{{ __('common.reject') }}</button>
                                            @endif
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

    {{-- Add expense slide-over --}}
    <x-ui.slide-over wire="showForm" :title="__('expenses.addTitle')">
        <form wire:submit="save" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('expenses.lblDescription')" wire:model="form_description" :error="$errors->first('form_description')" />
                <div class="grid grid-cols-2 gap-3">
                    <x-ui.input type="number" :label="__('expenses.lblAmount')" wire:model="form_amount" min="0" step="0.01" :error="$errors->first('form_amount')" />
                    <x-ui.input type="date" :label="__('expenses.lblDate')" wire:model="form_date" :error="$errors->first('form_date')" />
                </div>
                <x-ui.select :label="__('expenses.lblCategory')" wire:model="form_category" :options="collect($this->categories())->mapWithKeys(fn ($c) => [$c => __('exp.cat.'.$c)])->toArray()" />
                <x-ui.input :label="__('expenses.lblVendor')" wire:model="form_vendor" :error="$errors->first('form_vendor')" />
                <x-ui.select :label="__('exp.thMethod')" wire:model="form_method" :options="['cash' => __('expenses.methodCash'), 'card' => __('expenses.methodCard'), 'transfer' => __('expenses.methodTransfer')]" />
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('common.save') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    <x-ui.confirm-dialog wire="showDelete" :title="__('common.confirmDelete')" :description="__('common.confirmDeleteDesc')" action="deleteExpense" :actionLabel="__('common.delete')" />
</div>
