<div class="p-4 sm:p-6">
    <x-ui.page-header :title="__('emp.transfers.title')" :subtitle="__('emp.transfers.subtitle')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="openCreate">{{ __('emp.transfers.new') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <select wire:model.live="statusFilter" aria-label="{{ __('emp.transfers.colStatus') }}"
            class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
            <option value="all">{{ __('emp.transfers.allStatuses') }}</option>
            @foreach ($this->statuses() as $s)
                <option value="{{ $s }}">{{ __('emp.transfers.status'.ucfirst($s)) }}</option>
            @endforeach
        </select>
    </div>

    <x-ui.card padding="p-0">
        @if ($this->total === 0)
            <x-ui.empty-state :title="__('emp.transfers.empty')" :description="__('emp.transfers.emptyDesc')" icon="building-2">
                <x-ui.button icon="plus" wire:click="openCreate">{{ __('emp.transfers.new') }}</x-ui.button>
            </x-ui.empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[880px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.transfers.colEmployee') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.transfers.colFrom') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.transfers.colTo') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.transfers.colType') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.transfers.colUntil') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.transfers.colStatus') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->paginated as $r)
                            @php
                                $isTemp = ($r['type'] ?? '') === 'temporary';
                                $typeColor = $isTemp ? 'warning' : 'info';
                                $statusMeta = match ($r['status'] ?? 'pending') {
                                    'approved' => ['pill' => 'completed', 'label' => __('emp.transfers.statusApproved')],
                                    'rejected' => ['pill' => 'cancelled', 'label' => __('emp.transfers.statusRejected')],
                                    default => ['pill' => 'pending', 'label' => __('emp.transfers.statusPending')],
                                };
                            @endphp
                            <tr wire:key="tr-{{ $r['uuid'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3 font-medium text-fg">{{ $r['employee'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-fg-muted">{{ $r['from_branch'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-fg-muted">
                                    <span class="inline-flex items-center gap-1.5">
                                        <x-icon name="chevron-right" class="size-3.5 text-fg-subtle rtl:rotate-180" />
                                        {{ $r['to_branch'] ?: '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3"><x-ui.badge :color="$typeColor">{{ __('emp.transfers.type'.ucfirst($r['type'] ?? 'permanent')) }}</x-ui.badge></td>
                                <td class="px-4 py-3 text-fg-muted tabular-nums" dir="ltr">{{ $isTemp ? ($r['until_date'] ?: '—') : '—' }}</td>
                                <td class="px-4 py-3"><x-ui.status-pill :status="$statusMeta['pill']" :label="$statusMeta['label']" /></td>
                                <td class="px-4 py-3 text-end">
                                    <x-ui.dropdown :ariaLabel="__('common.actions')">
                                        <x-ui.dropdown-item icon="check" wire:click="approveTransfer('{{ $r['uuid'] }}')">{{ __('emp.transfers.approve') }}</x-ui.dropdown-item>
                                        <x-ui.dropdown-item icon="x" destructive wire:click="confirmReject('{{ $r['uuid'] }}')">{{ __('emp.transfers.reject') }}</x-ui.dropdown-item>
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

    {{-- Create slide-over --}}
    <x-ui.slide-over wire="showForm" :title="__('emp.transfers.addTitle')">
        <form wire:submit="createTransfer" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.select :label="__('emp.transfers.lblEmployee')" wire:model="form_employee" required
                    :placeholder="__('emp.transfers.employeePlaceholder')"
                    :options="collect($this->employeeOptions())->mapWithKeys(fn ($e) => [$e => $e])->toArray()"
                    :error="$errors->first('form_employee')" />
                <x-ui.select :label="__('emp.transfers.lblFrom')" wire:model="form_from_branch" required
                    :placeholder="__('emp.transfers.fromPlaceholder')"
                    :options="collect($this->branchOptions())->mapWithKeys(fn ($b) => [$b => $b])->toArray()"
                    :error="$errors->first('form_from_branch')" />
                <x-ui.select :label="__('emp.transfers.lblTo')" wire:model="form_to_branch" required
                    :placeholder="__('emp.transfers.toPlaceholder')"
                    :options="collect($this->branchOptions())->mapWithKeys(fn ($b) => [$b => $b])->toArray()"
                    :error="$errors->first('form_to_branch')" />
                <x-ui.select :label="__('emp.transfers.lblType')" wire:model.live="form_type" required
                    :options="collect($this->types())->mapWithKeys(fn ($t) => [$t => __('emp.transfers.type'.ucfirst($t))])->toArray()"
                    :error="$errors->first('form_type')" />
                @if ($form_type === 'temporary')
                    <x-ui.input type="date" :label="__('emp.transfers.lblUntil')" wire:model="form_until_date" required :error="$errors->first('form_until_date')" />
                @endif
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="createTransfer">{{ __('emp.transfers.submit') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>

    {{-- Reject confirmation with reason --}}
    <x-ui.confirm-dialog wire="showReject" :title="__('emp.transfers.rejectTitle')" :description="__('emp.transfers.rejectDesc')"
        action="rejectTransfer" :actionLabel="__('emp.transfers.reject')">
        <textarea wire:model="rejectReason" rows="2" placeholder="{{ __('emp.transfers.rejectReasonPlaceholder') }}"
            class="mt-3 w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-fg focus:border-primary-500 focus:outline-none"></textarea>
    </x-ui.confirm-dialog>
</div>
