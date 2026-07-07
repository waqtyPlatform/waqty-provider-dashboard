@php use Illuminate\Support\Carbon; @endphp

<div class="p-6">
    <x-ui.page-header :title="__('reviews.title')" :subtitle="__('reviews.subtitle')" />

    @if ($this->usingFallback())
        <x-ui.alert type="info" class="mb-4">{{ __('common.sampleData') }}</x-ui.alert>
    @endif

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.kpi-card :label="__('reviews.avgRating')" :value="number_format($this->kpis['avg'], 1)" icon="star" iconClass="bg-warning-light text-warning" />
        <x-ui.kpi-card :label="__('reviews.totalReviews')" :value="$this->kpis['total']" icon="star" />
        <x-ui.kpi-card :label="__('reviews.published')" :value="$this->kpis['published']" icon="check-circle-2" iconClass="bg-success-light text-success" />
        <x-ui.kpi-card :label="__('reviews.pending')" :value="$this->kpis['pending']" icon="clock" iconClass="bg-info-light text-info" />
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <select wire:model.live="statusFilter" aria-label="{{ __('common.status') }}" class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
            <option value="all">{{ __('reviews.allStatuses') }}</option>
            <option value="published">{{ __('reviews.statusPublished') }}</option>
            <option value="pending">{{ __('reviews.statusPending') }}</option>
            <option value="reported">{{ __('reviews.statusReported') }}</option>
        </select>
        <select wire:model.live="ratingFilter" aria-label="{{ __('reviews.avgRating') }}" class="rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
            <option value="all">{{ __('reviews.allRatings') }}</option>
            @foreach ([5, 4, 3, 2, 1] as $r)<option value="{{ $r }}">{{ $r }} ★</option>@endforeach
        </select>
    </div>

    @if (count($this->filtered) === 0)
        <x-ui.card><x-ui.empty-state :title="__('custProfile.noReviewsTitle')" :description="__('custProfile.noReviewsDesc')" icon="star" /></x-ui.card>
    @else
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            @foreach ($this->filtered as $rv)
                <x-ui.card wire:key="rv-{{ $rv->uuid }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <x-ui.avatar :name="$rv->customerName()" class="size-9 text-sm" />
                            <div>
                                <p class="font-medium text-fg">{{ $rv->customerName() }}</p>
                                <div class="flex items-center gap-0.5">
                                    @for ($i = 1; $i <= 5; $i++)<x-icon name="star" class="size-3.5 {{ $i <= $rv->rating ? 'text-warning' : 'text-line' }}" />@endfor
                                </div>
                            </div>
                        </div>
                        <x-ui.status-pill :status="$rv->status === 'reported' ? 'cancelled' : ($rv->status === 'pending' ? 'pending' : 'completed')" :label="match($rv->status) { 'reported' => __('reviews.statusReported'), 'pending' => __('reviews.statusPending'), default => __('reviews.statusPublished') }" />
                    </div>
                    @if ($rv->comment)<p class="mt-3 text-sm text-fg">{{ $rv->comment }}</p>@endif
                    <div class="mt-3 flex items-center justify-between border-t border-line pt-3">
                        <span class="text-xs text-fg-subtle">{{ $rv->created_at ? Carbon::parse($rv->created_at)->isoFormat('D MMM YYYY') : '' }}</span>
                        @if ($rv->status !== 'reported')
                            <button wire:click="openReport('{{ $rv->uuid }}')" class="inline-flex items-center gap-1.5 text-xs font-medium text-error hover:underline"><x-icon name="alert-triangle" class="size-3.5" />{{ __('reviews.report') }}</button>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-xs font-medium text-fg-subtle"><x-icon name="ban" class="size-3.5" />{{ __('reviews.reported') }}</span>
                        @endif
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    @endif

    {{-- Report modal --}}
    <x-ui.modal wire="showReport" maxWidth="max-w-md">
        <h3 class="text-lg font-semibold text-fg">{{ __('reviews.reportReview') }}</h3>
        <p class="mt-1 text-sm text-fg-muted">{{ __('reviews.reportDesc') }}</p>
        <form wire:submit="submitReport" class="mt-4 space-y-4">
            <x-ui.select :label="__('reviews.reportCategory')" wire:model="reportCategory" :options="['inappropriate' => 'Inappropriate content', 'spam' => 'Spam / advertising', 'fake' => 'Fake review', 'offensive' => 'Offensive language']" />
            <div>
                <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('reviews.reportReason') }}</label>
                <textarea wire:model="reportReason" rows="3" class="w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"></textarea>
                @error('reportReason') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-center justify-end gap-2">
                <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" variant="destructive" wire:loading.attr="disabled" wire:target="submitReport">{{ __('reviews.submitReport') }}</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
