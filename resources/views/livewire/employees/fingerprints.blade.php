<div class="p-4 sm:p-6">
    <x-ui.page-header :title="__('emp.fingerprints.title')" :subtitle="__('emp.fingerprints.subtitle')" />

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi-card :label="__('emp.fingerprints.kpiEnrolled')" :value="$this->kpis['enrolled']" icon="check-circle-2" iconClass="bg-success-light text-success" />
        <x-ui.kpi-card :label="__('emp.fingerprints.kpiPending')" :value="$this->kpis['pending']" icon="clock" iconClass="bg-warning-light text-warning" />
        <x-ui.kpi-card :label="__('emp.fingerprints.kpiFingers')" :value="$this->kpis['fingers']" icon="shield" iconClass="bg-primary-100 text-primary-600" />
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="relative min-w-64 flex-1">
            <span class="pointer-events-none absolute inset-y-0 start-0 grid w-10 place-items-center text-fg-subtle"><x-icon name="search" class="size-4" /></span>
            <input type="search" id="fp-search" aria-label="{{ __('common.search') }}" wire:model.live.debounce.300ms="search" placeholder="{{ __('emp.fingerprints.searchPlaceholder') }}"
                class="w-full rounded-lg border border-line bg-surface py-2.5 pe-3.5 ps-10 text-sm text-fg placeholder:text-fg-subtle focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
        </div>
        <select wire:model.live="statusFilter" aria-label="{{ __('common.status') }}" class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
            <option value="all">{{ __('emp.fingerprints.allStatuses') }}</option>
            @foreach ($this->statuses() as $status)
                <option value="{{ $status }}">{{ __('emp.fingerprints.status'.\Illuminate\Support\Str::studly($status)) }}</option>
            @endforeach
        </select>
    </div>

    <x-ui.card padding="p-0">
        @if (count($this->filtered) === 0)
            <x-ui.empty-state :title="__('emp.fingerprints.empty')" :description="__('emp.fingerprints.emptyDesc')" icon="shield" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.fingerprints.colEmployee') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.fingerprints.colStatus') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('emp.fingerprints.colFingers') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('emp.fingerprints.colLastSync') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->filtered as $r)
                            @php $enrolled = ($r['status'] ?? '') === 'enrolled'; @endphp
                            <tr wire:key="fp-{{ $r['uuid'] }}" class="border-b border-line last:border-0 hover:bg-surface-2">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-fg">{{ $r['employee'] ?? '—' }}</p>
                                    <p class="text-xs text-fg-subtle">{{ $r['department'] ?? '' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <x-ui.badge :color="$enrolled ? 'success' : 'warning'">
                                        {{ $enrolled ? __('emp.fingerprints.statusEnrolled') : __('emp.fingerprints.statusNotEnrolled') }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-4 py-3 text-end font-medium tabular-nums text-fg">{{ $enrolled ? (int) ($r['fingers'] ?? 0) : '—' }}</td>
                                <td class="px-4 py-3 text-fg-muted">{{ $r['last_sync'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-end">
                                    <x-ui.dropdown :ariaLabel="__('common.actions')">
                                        @if ($enrolled)
                                            <x-ui.dropdown-item icon="rotate-ccw" wire:click="openEnroll('{{ $r['uuid'] }}')">{{ __('emp.fingerprints.reenroll') }}</x-ui.dropdown-item>
                                            <x-ui.dropdown-item icon="trash-2" destructive wire:click="confirmClear('{{ $r['uuid'] }}')">{{ __('emp.fingerprints.clear') }}</x-ui.dropdown-item>
                                        @else
                                            <x-ui.dropdown-item icon="plus" wire:click="openEnroll('{{ $r['uuid'] }}')">{{ __('emp.fingerprints.enroll') }}</x-ui.dropdown-item>
                                        @endif
                                    </x-ui.dropdown>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    {{-- Enroll / re-enroll — simulated fingerprint scan --}}
    <x-ui.modal wire="showEnroll" maxWidth="max-w-md">
        <div
            x-data="{
                progress: 0,
                scanning: false,
                done: false,
                timer: null,
                run() {
                    if (this.scanning) return;
                    clearInterval(this.timer);
                    this.done = false;
                    this.scanning = true;
                    this.progress = 0;
                    this.timer = setInterval(() => {
                        this.progress = Math.min(100, this.progress + 5);
                        if (this.progress >= 100) {
                            clearInterval(this.timer);
                            this.scanning = false;
                            this.done = true;
                            $wire.enrollFingerprint();
                        }
                    }, 70);
                },
                stop() { clearInterval(this.timer); this.scanning = false; },
            }"
            x-on:fp-scan-reset.window="progress = 0; done = false; scanning = false; clearInterval(timer)"
            class="text-center"
        >
            <h3 class="text-lg font-semibold text-fg">{{ $isReenroll ? __('emp.fingerprints.reenrollTitle') : __('emp.fingerprints.enrollTitle') }}</h3>
            <p class="mt-1 text-sm text-fg-muted">{{ $enrollEmployee }}</p>

            {{-- Scan visual: pulsing fingerprint + sweeping read line --}}
            <div class="relative mx-auto my-5 grid size-28 place-items-center overflow-hidden rounded-2xl border border-line bg-surface-2">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"
                    class="size-14 transition-colors"
                    :class="done ? 'text-success' : (scanning ? 'text-primary-500 animate-pulse' : 'text-fg-subtle')">
                    <path d="M5.2 11a6.8 6.8 0 0 1 13.6 0" />
                    <path d="M7.6 13.4a4.4 4.4 0 0 1 8.8 0v.8" />
                    <path d="M10 14.4a2 2 0 0 1 4 0v2.6" />
                    <path d="M12 15.4V19" />
                    <path d="M9 18.6a10 10 0 0 0 .4 1.7" />
                    <path d="M15.1 18.9a10 10 0 0 1-.3 1.4" />
                </svg>
                <div x-show="scanning" x-cloak class="pointer-events-none absolute inset-x-3 h-0.5 rounded-full bg-primary-500 shadow-[0_0_12px_2px_rgba(99,102,241,0.55)]" :style="`top:${progress}%`"></div>
            </div>

            {{-- Progress bar --}}
            <div class="mx-auto h-1.5 w-full max-w-64 overflow-hidden rounded-full bg-surface-3">
                <div class="h-full rounded-full transition-all duration-100 ease-linear" :class="done ? 'bg-success' : 'bg-primary-500'" :style="`width:${progress}%`"></div>
            </div>
            <p class="mt-2 text-xs font-medium text-fg-muted"
                x-text="done ? '{{ __('emp.fingerprints.scanComplete') }}' : (scanning ? '{{ __('emp.fingerprints.scanning') }}' : '{{ __('emp.fingerprints.scanPrompt') }}')"></p>

            {{-- Fingers to register --}}
            <div class="mx-auto mt-5 max-w-56 text-start">
                <x-ui.input type="number" :label="__('emp.fingerprints.lblFingers')" wire:model="fingersCount" min="1" max="10" step="1" required :error="$errors->first('fingersCount')" />
            </div>

            <div class="mt-6 flex items-center justify-end gap-2">
                <x-ui.button type="button" variant="secondary" @click="stop(); open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="button" variant="primary" x-bind:disabled="scanning" @click="run()">
                    <svg x-show="scanning" x-cloak class="size-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z" /></svg>
                    <span x-text="scanning ? '{{ __('emp.fingerprints.scanning') }}' : '{{ __('emp.fingerprints.startScan') }}'">{{ __('emp.fingerprints.startScan') }}</span>
                </x-ui.button>
            </div>
        </div>
    </x-ui.modal>

    {{-- Clear confirmation --}}
    <x-ui.confirm-dialog wire="showClear" :title="__('emp.fingerprints.clearTitle')" :description="__('emp.fingerprints.clearDesc')"
        action="clearFingerprint" :actionLabel="__('emp.fingerprints.clear')" />
</div>
