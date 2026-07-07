@php
    use App\Support\Money;
    use Illuminate\Support\Carbon;
    $c = $this->customer();
    $isClinic = $provider->terminology()['requiresIntake'];
    $hasMedical = $c->allergies || $c->medical_conditions || $c->medications;
    $tabs = [
        'overview' => __('custProfile.tabOverview'),
        'statements' => __('custGroups.tabStatements'),
        'reviews' => __('custProfile.tabReviews'),
        'notes' => __('common.notes'),
    ];
@endphp

<div class="mx-auto max-w-5xl p-6">
    <a href="{{ route('customers') }}" wire:navigate class="mb-4 inline-flex items-center gap-1.5 text-sm text-fg-muted hover:text-fg">
        <x-icon name="chevron-left" class="size-4 rtl:rotate-180" />{{ __('sidebar.clients') }}
    </a>

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    {{-- Profile header --}}
    <x-ui.card class="mb-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-4">
                <x-ui.avatar :name="$c->name" class="size-16 text-xl" />
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-semibold text-fg">{{ $c->name }}</h1>
                        @if ($c->vip)<x-icon name="star" class="size-4 text-warning" />@endif
                        <x-ui.badge :color="$c->vip ? 'amber' : 'neutral'">{{ $c->groupName() }}</x-ui.badge>
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-fg-muted">
                        <span class="flex items-center gap-1.5" dir="ltr"><x-icon name="phone" class="size-3.5" />{{ $c->phone ?: '—' }}</span>
                        @if ($c->email)<span class="flex items-center gap-1.5" dir="ltr"><x-icon name="mail" class="size-3.5" />{{ $c->email }}</span>@endif
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if ($c->phone)
                    <x-ui.button href="tel:{{ $c->phone }}" variant="secondary" icon="phone">{{ __('common.call') }}</x-ui.button>
                @endif
                <x-ui.button wire:click="openEdit" icon="pencil">{{ __('custProfile.editProfile') }}</x-ui.button>
            </div>
        </div>

        {{-- KPI strip --}}
        <div class="mt-5 grid grid-cols-2 gap-4 border-t border-line pt-5 sm:grid-cols-4">
            <div><p class="text-xs text-fg-subtle">{{ __('custProfile.statVisits') }}</p><p class="text-lg font-semibold text-fg">{{ $c->total_visits }}</p></div>
            <div><p class="text-xs text-fg-subtle">{{ __('custProfile.statSpend') }}</p><p class="text-lg font-semibold text-primary-600">{{ Money::format($c->total_spent) }}</p></div>
            <div><p class="text-xs text-fg-subtle">{{ __('custProfile.statLastVisit') }}</p><p class="text-lg font-semibold text-fg">{{ $c->last_visit ? Carbon::parse($c->last_visit)->isoFormat('D MMM') : '—' }}</p></div>
            <div><p class="text-xs text-fg-subtle">{{ __('customers.colGroup') }}</p><p class="text-lg font-semibold text-fg">{{ $c->groupName() }}</p></div>
        </div>
    </x-ui.card>

    {{-- Medical alert (clinic-focused, shown whenever medical data exists) --}}
    @if ($hasMedical)
        <div class="mb-5 rounded-xl border border-error/30 bg-error-light/60 p-4">
            <div class="flex items-center gap-2 font-semibold text-error"><x-icon name="alert-triangle" class="size-4" />{{ __('custProfile.medicalNotes') }}</div>
            <div class="mt-2 grid grid-cols-1 gap-2 text-sm sm:grid-cols-3">
                @if ($c->allergies)<div><span class="font-medium text-fg">{{ __('custProfile.allergyAlert') }}</span> <span class="text-fg-muted">{{ $c->allergies }}</span></div>@endif
                @if ($c->medical_conditions)<div><span class="font-medium text-fg">{{ __('custProfile.conditionsLabel') }}</span> <span class="text-fg-muted">{{ $c->medical_conditions }}</span></div>@endif
                @if ($c->medications)<div><span class="font-medium text-fg">{{ __('custProfile.medicationsLabel') }}</span> <span class="text-fg-muted">{{ $c->medications }}</span></div>@endif
            </div>
        </div>
    @endif

    {{-- Tabs --}}
    <div class="mb-4 flex gap-1 border-b border-line">
        @foreach ($tabs as $key => $label)
            <button wire:click="$set('tab', '{{ $key }}')" class="relative px-4 py-2.5 text-sm font-medium transition-colors {{ $tab === $key ? 'text-primary-600' : 'text-fg-muted hover:text-fg' }}">
                {{ $label }}
                @if ($tab === $key)<span class="absolute inset-x-0 -bottom-px h-0.5 rounded bg-primary-500"></span>@endif
            </button>
        @endforeach
    </div>

    {{-- Overview --}}
    @if ($tab === 'overview')
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <x-ui.card>
                <h2 class="mb-3 font-semibold text-fg">{{ __('custProfile.contactInfo') }}</h2>
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between"><dt class="text-fg-subtle">{{ __('custProfile.lblPhone') }}</dt><dd class="text-fg" dir="ltr">{{ $c->phone ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-fg-subtle">{{ __('custProfile.lblEmail') }}</dt><dd class="text-fg" dir="ltr">{{ $c->email ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-fg-subtle">{{ __('custProfile.clientNum') }}</dt><dd class="font-mono text-fg">{{ $c->uuid }}</dd></div>
                </dl>
            </x-ui.card>
            <x-ui.card>
                <h2 class="mb-3 font-semibold text-fg">{{ __('custProfile.lblGeneralNotes') }}</h2>
                <p class="text-sm text-fg-muted">{{ $c->notes ?: __('common.noData') }}</p>
            </x-ui.card>
        </div>
    @endif

    {{-- Statements --}}
    @if ($tab === 'statements')
        <x-ui.card padding="p-0">
            @if (count($this->statements) === 0)
                <x-ui.empty-state :title="__('common.noData')" icon="wallet" />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[560px] text-sm">
                        <thead>
                            <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                                <th class="px-4 py-3 text-start">{{ __('custProfile.colDate') }}</th>
                                <th class="px-4 py-3 text-start">{{ __('txn.thDescription') }}</th>
                                <th class="px-4 py-3 text-end">{{ __('custGroups.tabStatements') }}</th>
                                <th class="px-4 py-3 text-end">{{ __('custProfile.colTotal') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->statements as $st)
                                <tr wire:key="st-{{ $st->uuid }}" class="border-b border-line last:border-0">
                                    <td class="px-4 py-3 text-fg-muted">{{ $st->created_at ? Carbon::parse($st->created_at)->isoFormat('D MMM, HH:mm') : '—' }}</td>
                                    <td class="px-4 py-3 text-fg">{{ $st->description }}</td>
                                    <td class="px-4 py-3 text-end font-medium tabular-nums {{ $st->isCredit() ? 'text-success' : 'text-error' }}">{{ $st->isCredit() ? '+' : '−' }}{{ Money::format($st->amount) }}</td>
                                    <td class="px-4 py-3 text-end tabular-nums text-fg">{{ Money::format($st->balance) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>
    @endif

    {{-- Reviews --}}
    @if ($tab === 'reviews')
        <div class="space-y-3">
            @forelse ($this->reviews as $rv)
                <x-ui.card wire:key="rv-{{ $rv->uuid }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-1">
                            @for ($i = 1; $i <= 5; $i++)
                                <x-icon name="star" class="size-4 {{ $i <= $rv->rating ? 'text-warning' : 'text-line' }}" />
                            @endfor
                        </div>
                        <time class="text-xs text-fg-subtle">{{ $rv->created_at ? Carbon::parse($rv->created_at)->isoFormat('D MMM YYYY') : '' }}</time>
                    </div>
                    @if ($rv->comment)<p class="mt-2 text-sm text-fg">{{ $rv->comment }}</p>@endif
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-fg-subtle">
                        @if ($rv->serviceName())<span class="rounded bg-surface-2 px-2 py-0.5">{{ $rv->serviceName() }}</span>@endif
                        @if ($rv->employeeName())<span>{{ __('custProfile.reviewedBy') }} {{ $rv->employeeName() }}</span>@endif
                    </div>
                </x-ui.card>
            @empty
                <x-ui.card><x-ui.empty-state :title="__('custProfile.noReviewsTitle')" :description="__('custProfile.noReviewsDesc')" icon="star" /></x-ui.card>
            @endforelse
        </div>
    @endif

    {{-- Notes --}}
    @if ($tab === 'notes')
        <x-ui.card class="mb-4">
            <form wire:submit="addNote" class="flex flex-col gap-3 sm:flex-row">
                <input type="text" wire:model="noteText" placeholder="{{ __('common.notes') }}…" class="flex-1 rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 @error('noteText') border-error @enderror">
                <x-ui.button type="submit" icon="plus" wire:loading.attr="disabled" wire:target="addNote">{{ __('custProfile.add') }}</x-ui.button>
            </form>
            @error('noteText') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
        </x-ui.card>

        <div class="space-y-3">
            @forelse ($this->staffNotes as $n)
                <x-ui.card wire:key="note-{{ $n->uuid }}" class="flex items-start gap-3">
                    <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-full bg-primary-100 text-xs font-semibold text-primary-700">{{ mb_substr($n->employeeName() ?? '?', 0, 1) }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-fg">{{ $n->note }}</p>
                        <p class="mt-1 text-xs text-fg-subtle">{{ __('custProfile.noteFrom') }}{{ $n->employeeName() ?: '—' }} · {{ $n->created_at ? Carbon::parse($n->created_at)->isoFormat('D MMM, HH:mm') : '' }}</p>
                    </div>
                </x-ui.card>
            @empty
                <x-ui.card><x-ui.empty-state :title="__('common.noData')" icon="inbox" /></x-ui.card>
            @endforelse
        </div>
    @endif

    {{-- Edit medical / notes slide-over --}}
    <x-ui.slide-over wire="showEdit" :title="__('custProfile.editMedicalTitle')">
        <form wire:submit="saveMedical" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex-1 space-y-4 p-5">
                <x-ui.input :label="__('custProfile.lblAllergiesCsv')" wire:model="form_allergies" :error="$errors->first('form_allergies')" />
                <x-ui.input :label="__('custProfile.lblChronicConditions')" wire:model="form_conditions" :error="$errors->first('form_conditions')" />
                <x-ui.input :label="__('custProfile.lblCurrentMeds')" wire:model="form_medications" :error="$errors->first('form_medications')" />
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('custProfile.lblGeneralNotes') }}</label>
                    <textarea wire:model="form_notes" rows="4" class="w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"></textarea>
                    @error('form_notes') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="saveMedical">{{ __('custProfile.btnSaveChanges') }}</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>
</div>
