@php
    use App\Support\Money;
    use App\Enums\BookingStatus;
    use Illuminate\Support\Carbon;
    $b = $this->booking();
    $status = $b->statusEnum();
    $steps = [BookingStatus::Pending, BookingStatus::Confirmed, BookingStatus::InProgress, BookingStatus::Completed];
    $stepIndex = array_search($status, $steps, true);
    $isOffPath = in_array($status, [BookingStatus::Cancelled, BookingStatus::NoShow], true);
    $actionLabel = [
        'confirmed' => __('dash.statusConfirmed'),
        'completed' => __('dash.statusCompleted'),
        'no_show' => __('dash.statusNoShow'),
    ];
@endphp

<div class="mx-auto max-w-5xl p-6">
    {{-- Back + header --}}
    <a href="{{ route('bookings.list') }}" wire:navigate class="mb-4 inline-flex items-center gap-1.5 text-sm text-fg-muted hover:text-fg">
        <x-icon name="chevron-left" class="size-4 rtl:rotate-180" />{{ __('sidebar.bookingList') }}
    </a>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-semibold text-fg">{{ $b->serviceName() }}</h1>
            <x-ui.status-pill :status="$b->status" :label="$status->label()" />
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($status->transitions() as $next)
                @if ($next === BookingStatus::Cancelled)
                    <x-ui.button variant="destructive" wire:click="confirmCancel">{{ __('dash.statusCancelled') }}</x-ui.button>
                @else
                    <x-ui.button variant="{{ $next === BookingStatus::NoShow ? 'secondary' : 'primary' }}" wire:click="changeStatus('{{ $next->value }}')" wire:loading.attr="disabled">
                        {{ $actionLabel[$next->value] ?? $next->label() }}
                    </x-ui.button>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Stepper --}}
    @unless ($isOffPath)
        <x-ui.card class="mb-5">
            <div class="flex items-center">
                @foreach ($steps as $i => $step)
                    <div class="flex items-center {{ $i < count($steps) - 1 ? 'flex-1' : '' }}">
                        <div class="flex items-center gap-2">
                            <span class="grid size-8 shrink-0 place-items-center rounded-full text-sm font-semibold {{ $i <= $stepIndex ? 'bg-primary-500 text-white' : 'bg-surface-3 text-fg-subtle' }}">
                                @if ($i < $stepIndex)<x-icon name="check" class="size-4" />@else{{ $i + 1 }}@endif
                            </span>
                            <span class="hidden text-sm font-medium sm:inline {{ $i <= $stepIndex ? 'text-fg' : 'text-fg-subtle' }}">{{ $step->label() }}</span>
                        </div>
                        @if ($i < count($steps) - 1)
                            <div class="mx-2 h-0.5 flex-1 rounded {{ $i < $stepIndex ? 'bg-primary-500' : 'bg-surface-3' }}"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    @endunless

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        {{-- Summary --}}
        <div class="space-y-5 lg:col-span-2">
            <x-ui.card>
                <h2 class="mb-4 font-semibold text-fg">{{ __('dash.details') }}</h2>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-fg-subtle">{{ __('sales.lblServices') }}</dt><dd class="mt-0.5 font-medium text-fg">{{ $b->serviceName() }}</dd></div>
                    <div><dt class="text-fg-subtle">{{ $provider->terminology()['staff'] }}</dt><dd class="mt-0.5 font-medium text-fg">{{ $b->employeeName() ?: '—' }}</dd></div>
                    <div><dt class="text-fg-subtle">{{ __('sales.lblDate') }}</dt><dd class="mt-0.5 font-medium text-fg">{{ $b->booking_date ? Carbon::parse($b->booking_date)->isoFormat('ddd, D MMM YYYY') : '—' }}</dd></div>
                    <div><dt class="text-fg-subtle">{{ __('sales.lblTime') }}</dt><dd class="mt-0.5 font-medium tabular-nums text-fg">{{ $b->hhmm() ?? '—' }}@if ($b->endHhmm()) – {{ $b->endHhmm() }}@endif</dd></div>
                    <div><dt class="text-fg-subtle">{{ __('branch.main') }}</dt><dd class="mt-0.5 font-medium text-fg">{{ $b->branch?->name ?: '—' }}</dd></div>
                    <div><dt class="text-fg-subtle">{{ __('dash.colRevenue') }}</dt><dd class="mt-0.5 font-semibold text-primary-600">{{ $b->price ? Money::format($b->price) : '—' }}</dd></div>
                </dl>
                @if ($b->payment_status)
                    <div class="mt-4 flex items-center gap-2 border-t border-line pt-4 text-sm">
                        <span class="text-fg-subtle">{{ __('sales.lblPaymentMethod') }}:</span>
                        <x-ui.status-pill :status="$b->payment_status" />
                    </div>
                @endif
                @if ($b->notes)
                    <div class="mt-4 border-t border-line pt-4">
                        <p class="text-xs text-fg-subtle">{{ __('common.notes') }}</p>
                        <p class="mt-1 text-sm text-fg">{{ $b->notes }}</p>
                    </div>
                @endif
            </x-ui.card>

            {{-- Activity timeline --}}
            <x-ui.card>
                <h2 class="mb-4 font-semibold text-fg">{{ __('common.activity') }}</h2>
                <ol class="relative space-y-4 ps-5">
                    @foreach ($this->activities as $act)
                        <li class="relative">
                            <span class="absolute -start-5 top-1 grid size-3 place-items-center">
                                <span class="size-2.5 rounded-full bg-primary-500 ring-4 ring-primary-500/15"></span>
                            </span>
                            @if (! $loop->last)<span class="absolute -start-[15px] top-3 h-full w-px bg-line"></span>@endif
                            <div class="flex items-baseline justify-between gap-2">
                                <p class="text-sm font-medium text-fg">{{ $act->label }}</p>
                                <time class="shrink-0 text-xs tabular-nums text-fg-subtle">{{ $act->created_at ? Carbon::parse($act->created_at)->isoFormat('D MMM, HH:mm') : '' }}</time>
                            </div>
                            @if ($act->actor_name)<p class="text-xs text-fg-subtle">{{ $act->actor_name }}</p>@endif
                        </li>
                    @endforeach
                </ol>
            </x-ui.card>
        </div>

        {{-- Client card --}}
        <div>
            <x-ui.card>
                <h2 class="mb-4 font-semibold text-fg">{{ $provider->terminology()['customer'] }}</h2>
                <div class="flex items-center gap-3">
                    <x-ui.avatar :name="$b->clientName()" class="size-12 text-base" />
                    <div class="min-w-0">
                        <p class="truncate font-medium text-fg">{{ $b->clientName() }}</p>
                        <p class="text-sm text-fg-subtle" dir="ltr">{{ $b->clientPhone() ?: '' }}</p>
                    </div>
                </div>
                @if ($b->clientPhone())
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <a href="tel:{{ $b->clientPhone() }}" class="flex items-center justify-center gap-1.5 rounded-lg border border-line py-2 text-sm text-fg hover:bg-surface-2"><x-icon name="phone" class="size-4" />{{ __('common.call') }}</a>
                        <a href="https://wa.me/2{{ $b->clientPhone() }}" target="_blank" rel="noopener" class="flex items-center justify-center gap-1.5 rounded-lg border border-line py-2 text-sm text-fg hover:bg-surface-2"><x-icon name="mail" class="size-4" />WhatsApp</a>
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>

    {{-- Cancel confirmation --}}
    <x-ui.confirm-dialog wire="showCancel" :title="__('bookings.cancelTitle')" :description="__('bookings.cancelDesc')" action="cancelBooking" :actionLabel="__('bookings.confirmCancel')">
        <textarea wire:model="cancelReason" rows="2" placeholder="{{ __('bookings.cancelReason') }}" class="mt-3 w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-fg focus:border-primary-500 focus:outline-none"></textarea>
    </x-ui.confirm-dialog>
</div>
