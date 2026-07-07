@php
    use App\Support\Money;
    use Illuminate\Support\Carbon;

    $inputClass = 'w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20';
    $selectClass = 'w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20';

    $stepLabels = [
        1 => __('returns.cancelDownPayment.step1Label'),
        2 => __('returns.cancelDownPayment.step2Label'),
        3 => __('returns.cancelDownPayment.step3Label'),
    ];
@endphp

<div class="mx-auto max-w-3xl p-4 sm:p-6">
    {{-- Back to returns --}}
    <a href="/returns" wire:navigate class="mb-4 inline-flex items-center gap-1.5 text-sm text-fg-muted transition hover:text-fg">
        <x-icon name="chevron-left" class="size-4 rtl:rotate-180" />
        {{ __('returns.cancelDownPayment.backToReturns') }}
    </a>

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-fg">{{ __('returns.cancelDownPayment.title') }}</h1>
        <p class="mt-1 text-sm text-fg-muted">{{ __('returns.cancelDownPayment.subtitle') }}</p>
    </div>

    @if ($done)
        {{-- Done panel --}}
        <x-ui.card>
            <div class="flex flex-col items-center py-6 text-center">
                <div class="mb-4 grid size-14 place-items-center rounded-full bg-success-light text-success">
                    <x-icon name="check-circle-2" class="size-8" />
                </div>
                <h2 class="text-lg font-semibold text-fg">{{ __('returns.cancelDownPayment.doneTitle') }}</h2>
                <p class="mt-1 max-w-sm text-sm text-fg-muted">{{ __('returns.cancelDownPayment.doneDesc') }}</p>

                @php($booking = $this->selectedBooking())
                @if ($booking)
                    <div class="mt-4 flex items-center gap-2 rounded-lg border border-line bg-surface-2 px-4 py-2 text-sm">
                        <span class="text-fg-muted">{{ __('returns.cancelDownPayment.amountToReturn') }}</span>
                        <span class="font-bold tabular-nums text-fg">{{ Money::format($booking['downPayment']) }}</span>
                    </div>
                @endif

                <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
                    <x-ui.button href="/returns" wire:navigate variant="secondary">{{ __('returns.cancelDownPayment.backToReturns') }}</x-ui.button>
                    <x-ui.button wire:click="startAnother" icon="plus">{{ __('returns.cancelDownPayment.startAnother') }}</x-ui.button>
                </div>
            </div>
        </x-ui.card>
    @else
        {{-- Segmented step progress --}}
        <div class="mb-2 flex items-center gap-2">
            @foreach ([1, 2, 3] as $s)
                <span class="h-1.5 flex-1 rounded-full transition-all {{ $s === $step ? 'bg-primary-500' : ($s < $step ? 'bg-primary-300' : 'bg-line') }}"></span>
            @endforeach
        </div>
        <p class="mb-5 text-xs font-medium text-fg-subtle">{{ __('returns.cancelDownPayment.stepCounter', ['current' => $step, 'total' => 3]) }} — {{ $stepLabels[$step] }}</p>

        <x-ui.card>
            {{-- Step 1: pick a booking --}}
            @if ($step === 1)
                <div class="space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-fg">{{ __('returns.cancelDownPayment.step1Title') }}</h2>
                        <p class="mt-1 text-sm text-fg-muted">{{ __('returns.cancelDownPayment.step1Desc') }}</p>
                    </div>

                    <div class="space-y-2.5">
                        @foreach ($this->bookings() as $booking)
                            @php($on = $bookingUuid === $booking['uuid'])
                            <button type="button" wire:key="bk-{{ $booking['uuid'] }}" wire:click="selectBooking('{{ $booking['uuid'] }}')"
                                @class([
                                    'flex w-full items-center gap-3 rounded-xl border p-3.5 text-start transition',
                                    'border-primary-500 bg-primary-50 ring-1 ring-primary-500' => $on,
                                    'border-line hover:border-line-strong hover:bg-surface-2' => ! $on,
                                ])>
                                <span @class([
                                    'grid size-10 shrink-0 place-items-center rounded-full',
                                    'bg-primary-500 text-white' => $on,
                                    'bg-surface-2 text-fg-muted' => ! $on,
                                ])>
                                    <x-icon name="calendar-days" class="size-5" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-fg">{{ $booking['client'] }}</p>
                                    <p class="truncate text-xs text-fg-muted">{{ $booking['service'] }} · {{ Carbon::parse($booking['date'])->translatedFormat('j F Y') }}</p>
                                </div>
                                <div class="shrink-0 text-end">
                                    <p class="text-xs text-fg-subtle">{{ __('returns.cancelDownPayment.downPaymentLabel') }}</p>
                                    <p class="text-sm font-semibold tabular-nums text-fg">{{ Money::format($booking['downPayment']) }}</p>
                                </div>
                                <x-icon name="check-circle-2" @class(['size-5 shrink-0', 'text-primary-500' => $on, 'text-transparent' => ! $on]) />
                            </button>
                        @endforeach
                    </div>

                    @error('bookingUuid') <p class="text-xs text-error">{{ $message }}</p> @enderror

                    <div class="flex justify-end pt-1">
                        <x-ui.button wire:click="next">{{ __('returns.cancelDownPayment.next') }}</x-ui.button>
                    </div>
                </div>
            @endif

            {{-- Step 2: reason + notes --}}
            @if ($step === 2)
                @php($booking = $this->selectedBooking())
                <div class="space-y-5">
                    <div>
                        <h2 class="text-base font-semibold text-fg">{{ __('returns.cancelDownPayment.step2Title') }}</h2>
                        <p class="mt-1 text-sm text-fg-muted">{{ __('returns.cancelDownPayment.step2Desc') }}</p>
                    </div>

                    @if ($booking)
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-line bg-surface-2 p-3.5">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-fg">{{ $booking['client'] }}</p>
                                <p class="truncate text-xs text-fg-muted">{{ $booking['service'] }} · {{ Carbon::parse($booking['date'])->translatedFormat('j F Y') }}</p>
                            </div>
                            <button type="button" wire:click="back" class="shrink-0 text-xs font-medium text-primary-600 hover:text-primary-700">{{ __('returns.cancelDownPayment.change') }}</button>
                        </div>
                    @endif

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('returns.cancelDownPayment.reasonLabel') }}</label>
                        <select wire:model="reason" class="{{ $selectClass }} @error('reason') border-error @enderror">
                            <option value="">{{ __('returns.cancelDownPayment.reasonPlaceholder') }}</option>
                            @foreach ($this->reasons() as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('reason') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('returns.cancelDownPayment.notesLabel') }} <span class="text-xs text-fg-subtle">({{ __('returns.cancelDownPayment.optional') }})</span></label>
                        <textarea wire:model="notes" rows="3" placeholder="{{ __('returns.cancelDownPayment.notesPlaceholder') }}" class="{{ $inputClass }} @error('notes') border-error @enderror"></textarea>
                        @error('notes') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                    </div>

                    @if ($booking)
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-primary-500/30 bg-primary-50 px-4 py-3">
                            <span class="flex items-center gap-2 text-sm font-medium text-primary-700"><x-icon name="wallet" class="size-4" />{{ __('returns.cancelDownPayment.amountToReturn') }}</span>
                            <span class="text-lg font-bold tabular-nums text-primary-700">{{ Money::format($booking['downPayment']) }}</span>
                        </div>
                    @endif

                    <div class="flex items-center justify-between gap-2 pt-1">
                        <x-ui.button variant="secondary" wire:click="back">{{ __('returns.cancelDownPayment.back') }}</x-ui.button>
                        <x-ui.button wire:click="next">{{ __('returns.cancelDownPayment.next') }}</x-ui.button>
                    </div>
                </div>
            @endif

            {{-- Step 3: confirm + submit --}}
            @if ($step === 3)
                @php($booking = $this->selectedBooking())
                <div class="space-y-5">
                    <div>
                        <h2 class="text-base font-semibold text-fg">{{ __('returns.cancelDownPayment.step3Title') }}</h2>
                        <p class="mt-1 text-sm text-fg-muted">{{ __('returns.cancelDownPayment.step3Desc') }}</p>
                    </div>

                    @if ($booking)
                        <dl class="divide-y divide-line rounded-xl border border-line">
                            <div class="flex items-center justify-between gap-3 px-4 py-3">
                                <dt class="text-sm text-fg-muted">{{ __('returns.cancelDownPayment.client') }}</dt>
                                <dd class="text-sm font-medium text-fg">{{ $booking['client'] }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 px-4 py-3">
                                <dt class="text-sm text-fg-muted">{{ __('returns.cancelDownPayment.service') }}</dt>
                                <dd class="text-sm font-medium text-fg">{{ $booking['service'] }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 px-4 py-3">
                                <dt class="text-sm text-fg-muted">{{ __('returns.cancelDownPayment.bookingDate') }}</dt>
                                <dd class="text-sm font-medium text-fg">{{ Carbon::parse($booking['date'])->translatedFormat('j F Y') }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 px-4 py-3">
                                <dt class="text-sm text-fg-muted">{{ __('returns.cancelDownPayment.reasonLabel') }}</dt>
                                <dd class="text-sm font-medium text-fg">{{ $this->reasons()[$reason] ?? '—' }}</dd>
                            </div>
                            @if (trim($notes) !== '')
                                <div class="flex items-start justify-between gap-3 px-4 py-3">
                                    <dt class="shrink-0 text-sm text-fg-muted">{{ __('returns.cancelDownPayment.notesLabel') }}</dt>
                                    <dd class="text-end text-sm font-medium text-fg">{{ $notes }}</dd>
                                </div>
                            @endif
                            <div class="flex items-center justify-between gap-3 bg-surface-2 px-4 py-3">
                                <dt class="text-sm font-semibold text-fg">{{ __('returns.cancelDownPayment.amountToReturn') }}</dt>
                                <dd class="text-base font-bold tabular-nums text-primary-700">{{ Money::format($booking['downPayment']) }}</dd>
                            </div>
                        </dl>
                    @endif

                    <x-ui.alert type="warning">{{ __('returns.cancelDownPayment.confirmWarning') }}</x-ui.alert>

                    <div class="flex items-center justify-between gap-2 pt-1">
                        <x-ui.button variant="secondary" wire:click="back">{{ __('returns.cancelDownPayment.back') }}</x-ui.button>
                        <x-ui.button wire:click="submit" loadingTarget="submit" icon="check">{{ __('returns.cancelDownPayment.submit') }}</x-ui.button>
                    </div>
                </div>
            @endif
        </x-ui.card>
    @endif
</div>
