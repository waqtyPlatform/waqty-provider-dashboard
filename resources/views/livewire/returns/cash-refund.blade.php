@php
    use App\Support\Money;
    $reasons = ['notSatisfied', 'billingError', 'doubleCharge', 'serviceCancelled', 'healthIssue', 'other'];
@endphp

<div class="mx-auto max-w-3xl p-4 sm:p-6">
    <a href="/returns" wire:navigate class="mb-4 inline-flex items-center gap-1.5 text-sm text-fg-muted hover:text-fg">
        <x-icon name="chevron-left" class="size-4 rtl:rotate-180" />{{ __('cashRefund.backToReturns') }}
    </a>

    @if ($done)
        {{-- Done panel --}}
        <div class="rounded-2xl border border-line bg-surface p-8 text-center shadow-md">
            <div class="mx-auto mb-4 grid size-14 place-items-center rounded-full bg-success-light text-success">
                <x-icon name="check" class="size-7" />
            </div>
            <h2 class="text-lg font-semibold text-fg">{{ __('cashRefund.doneTitle') }}</h2>
            <p class="mx-auto mt-1 max-w-md text-sm text-fg-muted">{{ __('cashRefund.doneDesc') }}</p>
            <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
                <x-ui.button href="/returns" wire:navigate variant="primary" icon="rotate-ccw">{{ __('cashRefund.backToReturns') }}</x-ui.button>
                <x-ui.button type="button" variant="outline" wire:click="startOver">{{ __('cashRefund.startAnother') }}</x-ui.button>
            </div>
        </div>
    @else
        <div class="mb-6">
            <h1 class="text-xl font-semibold text-fg">{{ __('cashRefund.title') }}</h1>
            <p class="mt-0.5 text-sm text-fg-muted">
                {{ match ($step) {
                    1 => __('cashRefund.step1Desc'),
                    2 => __('cashRefund.step2Desc'),
                    default => __('cashRefund.step3Desc'),
                } }}
            </p>
        </div>

        {{-- Segmented step progress --}}
        <div class="mb-6 flex items-center justify-center gap-2">
            @foreach ([1, 2, 3] as $s)
                <span class="h-1.5 rounded-full transition-all {{ $s === $step ? 'w-8 bg-primary-500' : ($s < $step ? 'w-8 bg-primary-300' : 'w-4 bg-line') }}"></span>
            @endforeach
        </div>

        <div class="rounded-2xl border border-line bg-surface p-5 shadow-md sm:p-6">
            {{-- Step 1: pick a sale --}}
            @if ($step === 1)
                <x-ui.alert type="info" class="mb-4">{{ __('cashRefund.sampleNote') }}</x-ui.alert>

                <div class="space-y-3">
                    @foreach ($this->transactions() as $t)
                        @php($on = $transactionUuid === $t['uuid'])
                        <button type="button" wire:key="txn-{{ $t['uuid'] }}" wire:click="selectTransaction('{{ $t['uuid'] }}')"
                            @class([
                                'flex w-full items-start justify-between gap-3 rounded-xl border p-4 text-start transition',
                                'border-primary-500 bg-primary-50 ring-1 ring-primary-500' => $on,
                                'border-line hover:border-line-strong' => ! $on,
                            ])>
                            <div class="min-w-0">
                                <p class="font-medium text-fg">{{ $t['client'] }}</p>
                                <p class="mt-0.5 text-sm text-fg-muted">{{ $t['service'] }}</p>
                                <p class="mt-1 text-xs text-fg-subtle">{{ $t['uuid'] }} · {{ $t['date'] }}</p>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-1.5">
                                <span class="font-semibold tabular-nums text-fg">{{ Money::format($t['amount']) }}</span>
                                <span @class([
                                    'grid size-5 place-items-center rounded-full border transition',
                                    'border-primary-500 bg-primary-500 text-white' => $on,
                                    'border-line text-transparent' => ! $on,
                                ])><x-icon name="check" class="size-3" /></span>
                            </div>
                        </button>
                    @endforeach
                </div>
                @error('transactionUuid') <p class="mt-2 text-xs text-error">{{ $message }}</p> @enderror

                <div class="mt-6 flex justify-end">
                    <x-ui.button type="button" wire:click="next">{{ __('cashRefund.next') }}</x-ui.button>
                </div>
            @endif

            {{-- Step 2: choose items + refund amounts --}}
            @if ($step === 2)
                @php($txn = $this->selectedTransaction())
                @if ($txn)
                    <p class="text-sm font-medium text-fg">{{ __('cashRefund.itemsLabel') }}</p>
                    <p class="mt-0.5 text-xs text-fg-subtle">{{ __('cashRefund.itemsHint') }}</p>

                    <div class="mt-4 space-y-2.5">
                        @foreach ($txn['items'] as $item)
                            @php($on = in_array($item['id'], $selectedItems, true))
                            <div wire:key="item-{{ $item['id'] }}" @class([
                                'rounded-xl border p-3.5 transition',
                                'border-primary-300 bg-primary-50/40' => $on,
                                'border-line' => ! $on,
                            ])>
                                <div class="flex items-center justify-between gap-3">
                                    <label class="flex items-center gap-2.5">
                                        <input type="checkbox" wire:click="toggleItem('{{ $item['id'] }}')" @checked($on)
                                            class="size-4 rounded border-line text-primary-600 focus:ring-primary-500/30">
                                        <span class="font-medium text-fg">{{ $item['name'] }}</span>
                                    </label>
                                    <span class="shrink-0 text-xs text-fg-subtle">{{ __('cashRefund.originalAmount') }}: <span class="tabular-nums">{{ Money::format($item['amount']) }}</span></span>
                                </div>

                                @if ($on)
                                    <div class="mt-3 flex flex-wrap items-center gap-2 ps-7">
                                        <label class="text-xs text-fg-muted">{{ __('cashRefund.refundAmount') }}</label>
                                        <input type="number" step="0.01" min="0" max="{{ Money::fromMinor($item['amount']) }}" dir="ltr"
                                            wire:model.live="itemAmounts.{{ $item['id'] }}"
                                            @class([
                                                'w-32 rounded-lg border bg-surface px-3 py-1.5 text-sm tabular-nums text-fg focus:outline-none focus:ring-2 focus:ring-primary-500/20',
                                                'border-error' => $errors->has('itemAmounts.'.$item['id']),
                                                'border-line focus:border-primary-500' => ! $errors->has('itemAmounts.'.$item['id']),
                                            ])>
                                        <span class="text-xs text-fg-subtle">{{ Money::label() }}</span>
                                    </div>
                                    @error('itemAmounts.'.$item['id']) <p class="mt-1.5 ps-7 text-xs text-error">{{ $message }}</p> @enderror
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @error('selectedItems') <p class="mt-2 text-xs text-error">{{ $message }}</p> @enderror

                    <div class="mt-4 flex items-center justify-between rounded-xl border border-line bg-surface-2 px-4 py-3">
                        <span class="text-sm font-medium text-fg">{{ __('cashRefund.runningTotal') }}</span>
                        <span class="text-lg font-semibold tabular-nums text-primary-600">{{ Money::format($this->refundTotalMinor()) }}</span>
                    </div>

                    <div class="mt-6 flex items-center justify-between gap-2">
                        <x-ui.button type="button" variant="outline" wire:click="back">{{ __('cashRefund.back') }}</x-ui.button>
                        <x-ui.button type="button" wire:click="next">{{ __('cashRefund.next') }}</x-ui.button>
                    </div>
                @endif
            @endif

            {{-- Step 3: reason + notes + confirm --}}
            @if ($step === 3)
                @php($txn = $this->selectedTransaction())
                @if ($txn)
                    <div class="space-y-5">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('cashRefund.reasonLabel') }}<span class="text-error"> *</span></label>
                            <select wire:model="reason"
                                @class([
                                    'w-full rounded-lg border bg-surface px-3 py-2.5 text-sm text-fg focus:outline-none focus:ring-2 focus:ring-primary-500/20',
                                    'border-error' => $errors->has('reason'),
                                    'border-line focus:border-primary-500' => ! $errors->has('reason'),
                                ])>
                                <option value="">{{ __('cashRefund.reasonPlaceholder') }}</option>
                                @foreach ($reasons as $r)
                                    <option value="{{ $r }}">{{ __('cashRefund.reasons.'.$r) }}</option>
                                @endforeach
                            </select>
                            @error('reason') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('cashRefund.notesLabel') }} <span class="text-xs text-fg-subtle">({{ __('cashRefund.optional') }})</span></label>
                            <textarea wire:model="notes" rows="3" placeholder="{{ __('cashRefund.notesPlaceholder') }}"
                                class="w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"></textarea>
                        </div>

                        {{-- Confirm summary --}}
                        <div class="rounded-xl border border-line bg-surface-2 p-4">
                            <p class="mb-3 text-sm font-semibold text-fg">{{ __('cashRefund.summaryTitle') }}</p>
                            <dl class="space-y-2 text-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-fg-muted">{{ __('cashRefund.client') }}</dt>
                                    <dd class="font-medium text-fg">{{ $txn['client'] }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-fg-muted">{{ __('cashRefund.txnLabel') }}</dt>
                                    <dd class="font-medium tabular-nums text-fg">{{ $txn['uuid'] }}</dd>
                                </div>
                                <div class="border-t border-line pt-2">
                                    <dt class="mb-1.5 text-fg-muted">{{ __('cashRefund.itemsLabel') }}</dt>
                                    <dd>
                                        <ul class="space-y-1">
                                            @foreach ($this->chosenItems() as $item)
                                                <li class="flex items-center justify-between gap-3">
                                                    <span class="text-fg">{{ $item['name'] }}</span>
                                                    <span class="tabular-nums text-fg">{{ Money::format($this->itemRefundMinor($item['id'])) }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </dd>
                                </div>
                                <div class="flex items-center justify-between gap-3 border-t border-line pt-2">
                                    <dt class="font-semibold text-fg">{{ __('cashRefund.runningTotal') }}</dt>
                                    <dd class="text-lg font-semibold tabular-nums text-primary-600">{{ Money::format($this->refundTotalMinor()) }}</dd>
                                </div>
                                @if ($reason)
                                    <div class="flex items-center justify-between gap-3 border-t border-line pt-2">
                                        <dt class="text-fg-muted">{{ __('cashRefund.reasonLabel') }}</dt>
                                        <dd class="font-medium text-fg">{{ __('cashRefund.reasons.'.$reason) }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>

                        <div class="flex items-center justify-between gap-2">
                            <x-ui.button type="button" variant="outline" wire:click="back">{{ __('cashRefund.back') }}</x-ui.button>
                            <x-ui.button type="button" variant="primary" icon="check" wire:click="submit" loadingTarget="submit">{{ __('cashRefund.submit') }}</x-ui.button>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    @endif
</div>
