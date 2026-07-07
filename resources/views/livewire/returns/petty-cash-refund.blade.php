@php
    use App\Support\Money;
    $inputClass = 'w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20';
    $entry = $this->selectedEntry();
@endphp

<div class="mx-auto max-w-3xl p-4 sm:p-6">
    {{-- Back to returns --}}
    <a href="{{ route('returns') }}" wire:navigate class="mb-4 inline-flex items-center gap-1.5 text-sm text-fg-muted hover:text-fg">
        <x-icon name="chevron-left" class="size-4 rtl:rotate-180" />{{ __('returns.pettyCashRefund.backToReturns') }}
    </a>

    <x-ui.page-header :title="__('returns.pettyCashRefund.title')" :subtitle="__('returns.pettyCashRefund.subtitle')" />

    @unless ($done)
        {{-- Segmented step progress --}}
        <div class="mb-6 flex items-center gap-2">
            @foreach ([1, 2, 3] as $s)
                <span class="h-1.5 flex-1 rounded-full transition-all {{ $s === $step ? 'bg-primary-500' : ($s < $step ? 'bg-primary-300' : 'bg-line') }}"></span>
            @endforeach
        </div>
        <p class="mb-4 text-xs font-medium text-fg-subtle">
            {{ __('returns.pettyCashRefund.stepCounter', ['current' => $step, 'total' => 3]) }}
        </p>
    @endunless

    {{-- ── Step 1: pick a petty-cash entry ───────────────────────── --}}
    @if ($step === 1 && ! $done)
        <x-ui.card>
            <h2 class="text-base font-semibold text-fg">{{ __('returns.pettyCashRefund.step1Title') }}</h2>
            <p class="mt-1 text-sm text-fg-muted">{{ __('returns.pettyCashRefund.step1Desc') }}</p>

            <div class="mt-3 flex items-center gap-2 rounded-lg border border-info/30 bg-info-light px-3.5 py-2 text-xs text-info">
                <x-icon name="alert-triangle" class="size-4 shrink-0" />{{ __('returns.pettyCashRefund.demoNote') }}
            </div>

            <div class="mt-4 space-y-2.5">
                @foreach ($this->entries() as $e)
                    @php($on = $pettyCashUuid === $e['uuid'])
                    <button type="button" wire:key="pc-{{ $e['uuid'] }}" wire:click="selectEntry('{{ $e['uuid'] }}')"
                        @class([
                            'flex w-full items-start gap-3 rounded-xl border p-3.5 text-start transition',
                            'border-primary-500 bg-primary-50 ring-1 ring-primary-500' => $on,
                            'border-line hover:border-line-strong' => ! $on,
                        ])>
                        <span @class([
                            'mt-0.5 grid size-9 shrink-0 place-items-center rounded-lg',
                            'bg-primary-500 text-white' => $on,
                            'bg-surface-2 text-fg-muted' => ! $on,
                        ])>
                            <x-icon name="receipt" class="size-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="font-medium text-fg">{{ $e['category'] }}</span>
                                <span class="rounded-full bg-surface-2 px-2 py-0.5 text-xs text-fg-subtle">{{ $e['date'] }}</span>
                            </span>
                            <span class="mt-0.5 block text-sm text-fg-muted">{{ $e['description'] }}</span>
                            <span class="mt-1 block text-xs text-fg-subtle">{{ __('returns.pettyCashRefund.loggedBy') }} {{ $e['employee'] }}</span>
                        </span>
                        <span class="shrink-0 text-end">
                            <span class="block font-semibold tabular-nums text-fg">{{ Money::format($e['amount']) }}</span>
                            @if ($on)
                                <span class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-primary-600"><x-icon name="check" class="size-3.5" />{{ __('returns.pettyCashRefund.selected') }}</span>
                            @endif
                        </span>
                    </button>
                @endforeach
            </div>

            @error('pettyCashUuid') <p class="mt-2 text-xs text-error">{{ $message }}</p> @enderror

            <div class="mt-5 flex justify-end">
                <x-ui.button wire:click="next">{{ __('returns.pettyCashRefund.next') }}</x-ui.button>
            </div>
        </x-ui.card>
    @endif

    {{-- ── Step 2: reason + notes ────────────────────────────────── --}}
    @if ($step === 2 && ! $done)
        <x-ui.card>
            <h2 class="text-base font-semibold text-fg">{{ __('returns.pettyCashRefund.step2Title') }}</h2>
            <p class="mt-1 text-sm text-fg-muted">{{ __('returns.pettyCashRefund.step2Desc') }}</p>

            @if ($entry)
                <div class="mt-4 flex items-center justify-between rounded-xl border border-line bg-surface-2 p-3.5">
                    <div class="min-w-0">
                        <p class="font-medium text-fg">{{ $entry['category'] }}</p>
                        <p class="mt-0.5 truncate text-sm text-fg-muted">{{ $entry['description'] }}</p>
                    </div>
                    <span class="ms-3 shrink-0 font-semibold tabular-nums text-fg">{{ Money::format($entry['amount']) }}</span>
                </div>
            @endif

            <div class="mt-4 space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('returns.pettyCashRefund.reasonLabel') }}<span class="text-error"> *</span></label>
                    <select wire:model="reason" class="{{ $inputClass }} @error('reason') border-error @enderror">
                        <option value="">{{ __('returns.pettyCashRefund.reasonPlaceholder') }}</option>
                        @foreach ($this->reasonOptions() as $r)
                            <option value="{{ $r }}">{{ $r }}</option>
                        @endforeach
                    </select>
                    @error('reason') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('returns.pettyCashRefund.notesLabel') }} <span class="text-xs text-fg-subtle">({{ __('returns.pettyCashRefund.optional') }})</span></label>
                    <textarea wire:model="notes" rows="3" placeholder="{{ __('returns.pettyCashRefund.notesPlaceholder') }}" class="{{ $inputClass }} @error('notes') border-error @enderror"></textarea>
                    @error('notes') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-5 flex items-center justify-between gap-2">
                <x-ui.button variant="secondary" wire:click="back">{{ __('returns.pettyCashRefund.back') }}</x-ui.button>
                <x-ui.button wire:click="next">{{ __('returns.pettyCashRefund.next') }}</x-ui.button>
            </div>
        </x-ui.card>
    @endif

    {{-- ── Step 3: confirm & submit ──────────────────────────────── --}}
    @if ($step === 3 && ! $done)
        <x-ui.card>
            <h2 class="text-base font-semibold text-fg">{{ __('returns.pettyCashRefund.step3Title') }}</h2>
            <p class="mt-1 text-sm text-fg-muted">{{ __('returns.pettyCashRefund.step3Desc') }}</p>

            <dl class="mt-4 divide-y divide-line rounded-xl border border-line">
                <div class="flex items-start justify-between gap-3 px-4 py-3">
                    <dt class="text-sm text-fg-muted">{{ __('returns.pettyCashRefund.summaryCategory') }}</dt>
                    <dd class="text-end text-sm font-medium text-fg">{{ $entry['category'] ?? '—' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-3 px-4 py-3">
                    <dt class="text-sm text-fg-muted">{{ __('returns.pettyCashRefund.summaryDescription') }}</dt>
                    <dd class="text-end text-sm text-fg">{{ $entry['description'] ?? '—' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-3 px-4 py-3">
                    <dt class="text-sm text-fg-muted">{{ __('returns.pettyCashRefund.summaryReason') }}</dt>
                    <dd class="text-end text-sm text-fg">{{ $reason ?: '—' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-3 px-4 py-3">
                    <dt class="text-sm text-fg-muted">{{ __('returns.pettyCashRefund.summaryNotes') }}</dt>
                    <dd class="text-end text-sm text-fg">{{ $notes ?: '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3 bg-surface-2 px-4 py-3">
                    <dt class="text-sm font-medium text-fg">{{ __('returns.pettyCashRefund.summaryAmount') }}</dt>
                    <dd class="text-end text-base font-semibold tabular-nums text-primary-600">{{ $entry ? Money::format($entry['amount']) : '—' }}</dd>
                </div>
            </dl>

            <div class="mt-5 flex items-center justify-between gap-2">
                <x-ui.button variant="secondary" wire:click="back">{{ __('returns.pettyCashRefund.back') }}</x-ui.button>
                <x-ui.button wire:click="submit" icon="check" loadingTarget="submit">{{ __('returns.pettyCashRefund.confirmSubmit') }}</x-ui.button>
            </div>
        </x-ui.card>
    @endif

    {{-- ── Success panel ─────────────────────────────────────────── --}}
    @if ($done)
        <x-ui.card>
            <div class="flex flex-col items-center py-6 text-center">
                <div class="grid size-14 place-items-center rounded-full bg-success-light text-success">
                    <x-icon name="check" class="size-7" />
                </div>
                <h2 class="mt-4 text-lg font-semibold text-fg">{{ __('returns.pettyCashRefund.successTitle') }}</h2>
                <p class="mt-1 max-w-md text-sm text-fg-muted">{{ __('returns.pettyCashRefund.successMessage') }}</p>

                <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
                    <x-ui.button :href="route('returns')" wire:navigate>{{ __('returns.pettyCashRefund.backToList') }}</x-ui.button>
                    <x-ui.button variant="secondary" wire:click="startAnother" icon="rotate-ccw">{{ __('returns.pettyCashRefund.startAnother') }}</x-ui.button>
                </div>
            </div>
        </x-ui.card>
    @endif
</div>
