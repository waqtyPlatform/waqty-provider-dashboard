@php
    use App\Support\Money;
    use Illuminate\Support\Carbon;

    $groups = $this->schedule();
    $employees = $this->employees();
    $dateLabel = Carbon::parse($date)->isoFormat('dddd, D MMMM YYYY');
    $grandCount = array_sum(array_map(fn ($g) => $g['count'], $groups));
    $grandRevenue = array_sum(array_map(fn ($g) => $g['revenue'], $groups));
@endphp

<div class="p-6">
    {{-- Print rules: hide the app chrome, show only the sheet in flat black-on-white. --}}
    <style>
        @media print {
            @page { margin: 1.5cm; }
            .no-print { display: none !important; }
            body * { visibility: hidden !important; }
            .print-sheet, .print-sheet * { visibility: visible !important; }
            .print-sheet {
                position: absolute;
                inset-block-start: 0;
                inset-inline-start: 0;
                width: 100%;
                margin: 0;
                padding: 0;
            }
            .print-sheet,
            .print-sheet * {
                color: #000 !important;
                background-color: transparent !important;
                border-color: #000 !important;
                box-shadow: none !important;
            }
            .print-block {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>

    {{-- Controls (screen only) --}}
    <div class="no-print mb-6 flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-xl font-semibold text-fg">{{ __('bookings.printTitle') }}</h1>
            <p class="mt-0.5 text-sm text-fg-muted">{{ __('bookings.printDesc') }}</p>
        </div>
        <div class="flex flex-wrap items-end gap-2">
            <div>
                <label for="print-date" class="mb-1.5 block text-sm font-medium text-fg">{{ __('bookings.date') }}</label>
                <input id="print-date" type="date" wire:model.live="date"
                    class="rounded-lg border border-line bg-surface px-3 py-2 text-sm text-fg focus:border-primary-500 focus:outline-none" />
            </div>
            <div>
                <label for="print-emp" class="mb-1.5 block text-sm font-medium text-fg">{{ __('bookings.employee') }}</label>
                <select id="print-emp" wire:model.live="employeeFilter"
                    class="rounded-lg border border-line bg-surface px-3 py-2 text-sm text-fg focus:border-primary-500 focus:outline-none">
                    <option value="all">{{ __('common.allStaff') }}</option>
                    @foreach ($employees as $emp)
                        <option value="{{ $emp->uuid }}">{{ $emp->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" x-data @click="window.print()"
                class="inline-flex items-center gap-2 rounded-lg bg-primary-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500/40">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                    <path d="M6 9V2h12v7" />
                    <rect x="6" y="14" width="12" height="8" rx="1" />
                </svg>
                {{ __('common.print') }}
            </button>
        </div>
    </div>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4 no-print">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    {{-- Printable sheet --}}
    <div class="print-sheet rounded-xl border border-line bg-surface p-6">
        {{-- Sheet header --}}
        <div class="mb-5 flex flex-wrap items-start justify-between gap-4 border-b border-line pb-4">
            <div class="min-w-0">
                <h2 class="text-lg font-bold text-fg">{{ $provider->name() ?? config('app.name', 'Waqty') }}</h2>
                <p class="mt-0.5 text-sm text-fg-muted">{{ __('bookings.printTitle') }}</p>
            </div>
            <div class="text-end">
                <p class="text-sm font-semibold text-fg">{{ $dateLabel }}</p>
                <p class="mt-0.5 text-xs text-fg-muted">
                    {{ __('bookings.printCount', ['count' => $grandCount]) }} · {{ Money::format($grandRevenue) }}
                </p>
            </div>
        </div>

        @forelse ($groups as $group)
            @php $emp = $group['employee']; @endphp
            <div wire:key="grp-{{ $emp->uuid ?? 'unassigned-'.$loop->index }}"
                class="print-block mb-6 break-inside-avoid last:mb-0">
                {{-- Employee header --}}
                <div class="mb-2 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        @if ($emp)
                            <x-ui.avatar :name="$emp->name" size="size-8" class="no-print text-xs" />
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-fg">{{ $emp->name }}</p>
                                @if ($emp->position)
                                    <p class="truncate text-xs text-fg-muted">{{ $emp->position }}</p>
                                @endif
                            </div>
                        @else
                            <p class="font-semibold text-fg">{{ __('bookings.printUnassigned') }}</p>
                        @endif
                    </div>
                    <span class="whitespace-nowrap text-xs font-medium text-fg-muted">
                        {{ __('bookings.printCount', ['count' => $group['count']]) }}
                    </span>
                </div>

                {{-- Schedule table --}}
                <div class="overflow-x-auto rounded-lg border border-line">
                    <table class="w-full border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-line bg-surface-2/60 text-xs font-semibold uppercase text-fg-muted">
                                <th class="px-3 py-2 text-start">{{ __('bookings.time') }}</th>
                                <th class="px-3 py-2 text-start">{{ __('bookings.client') }}</th>
                                <th class="px-3 py-2 text-start">{{ __('bookings.service') }}</th>
                                <th class="px-3 py-2 text-start">{{ __('common.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($group['bookings'] as $b)
                                <tr wire:key="row-{{ $b->uuid }}"
                                    class="border-b border-line/70 last:border-b-0 {{ $b->status === 'cancelled' ? 'text-fg-subtle line-through' : 'text-fg' }}">
                                    <td class="whitespace-nowrap px-3 py-2 font-medium tabular-nums">
                                        {{ $b->hhmm() }}@if ($b->endHhmm())<span class="text-fg-subtle"> – {{ $b->endHhmm() }}</span>@endif
                                    </td>
                                    <td class="px-3 py-2">{{ $b->clientName() }}</td>
                                    <td class="px-3 py-2 text-fg-muted">{{ $b->serviceName() }}</td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex items-center gap-1.5">
                                            <span class="size-2 shrink-0 rounded-full" style="background-color: {{ $b->statusEnum()->color() }}"></span>
                                            {{ $b->statusEnum()->label() }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-4 text-center text-sm text-fg-subtle">
                                        {{ __('bookings.printBlockEmpty') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="py-12 text-center text-sm text-fg-muted">{{ __('bookings.printEmpty') }}</div>
        @endforelse
    </div>
</div>
