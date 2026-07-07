@php
    $stepLabels = [
        1 => __('settings.services.new.step1'),
        2 => __('settings.services.new.step2'),
        3 => __('settings.services.new.step3'),
        4 => __('settings.services.new.step4'),
        5 => __('settings.services.new.step5'),
        6 => __('settings.services.new.review'),
    ];
    $stepHeadings = [
        1 => __('settings.services.new.basicInfo'),
        2 => __('settings.services.new.pricing'),
        3 => __('settings.services.new.durationTitle'),
        4 => __('settings.services.new.resourcing'),
        5 => __('settings.services.new.commission'),
        6 => __('settings.services.new.review'),
    ];
    $categoryOptions = [
        'Hair' => __('settings.services.new.catHair'),
        'Nails' => __('settings.services.new.catNails'),
        'Spa' => __('settings.services.new.catSpa'),
    ];
    $resourceOptions = [
        'none' => __('settings.services.new.resNone'),
        'chair' => __('settings.services.new.resChair'),
        'room' => __('settings.services.new.resRoom'),
        'equipment' => __('settings.services.new.resEquipment'),
    ];
    $inputClass = 'w-full rounded-lg border bg-surface px-3.5 py-2.5 text-sm text-fg placeholder:text-fg-subtle focus:outline-none focus:ring-2 focus:ring-primary-500/20';
@endphp

<div class="mx-auto max-w-2xl p-6">
    <x-ui.page-header :title="__('settings.services.new.title')" :subtitle="__('settings.services.new.subtitle')">
        <x-slot:actions>
            <x-ui.button variant="outline" :href="route('settings.services')" wire:navigate>{{ __('settings.services.new.cancel') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Segmented step progress (like onboarding) --}}
    <div class="mb-2 flex items-center gap-1.5">
        @foreach (range(1, 6) as $s)
            <button type="button" wire:click="goToStep({{ $s }})" @disabled($s >= $step)
                @class([
                    'h-1.5 flex-1 rounded-full transition-all',
                    'bg-primary-500' => $s === $step,
                    'cursor-pointer bg-primary-300 hover:bg-primary-400' => $s < $step,
                    'cursor-default bg-line' => $s > $step,
                ])
                aria-label="{{ $stepLabels[$s] }}"></button>
        @endforeach
    </div>
    <p class="mb-5 text-xs font-medium text-fg-subtle">
        {{ __('settings.services.new.stepTitle') }} {{ $step }} {{ __('settings.services.new.of') }} 6 — {{ $stepLabels[$step] }}
    </p>

    <x-ui.card>
        <h2 class="mb-4 text-base font-semibold text-fg">{{ $stepHeadings[$step] }}</h2>

        {{-- Step 1: basics --}}
        @if ($step === 1)
            <div class="space-y-4">
                <x-ui.input :label="__('settings.services.new.svcName')" required wire:model="name"
                    :placeholder="__('settings.services.new.svcNamePh')" :error="$errors->first('name')" />

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('settings.services.new.desc') }}</label>
                    <textarea wire:model="description" rows="3"
                        class="{{ $inputClass }} {{ $errors->has('description') ? 'border-error' : 'border-line focus:border-primary-500' }}"></textarea>
                    @error('description')
                        <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                    @else
                        <p class="mt-1.5 text-xs text-fg-subtle">{{ __('settings.services.new.descHint') }}</p>
                    @enderror
                </div>

                <x-ui.select :label="__('settings.services.new.category')" wire:model="category"
                    :options="$categoryOptions" :placeholder="__('settings.services.new.resNone')"
                    :error="$errors->first('category')" />
            </div>
        @endif

        {{-- Step 2: pricing --}}
        @if ($step === 2)
            <div class="space-y-4">
                <x-ui.input type="number" min="0" step="0.01" dir="ltr" inputmode="decimal"
                    :label="__('settings.services.new.price')" wire:model="price" placeholder="0.00"
                    :hint="__('settings.services.new.priceHint')" :error="$errors->first('price')" />

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('settings.services.new.tierNote') }}</label>
                    <input type="text" wire:model="tierNote" placeholder="{{ __('settings.services.new.tierNotePh') }}"
                        class="{{ $inputClass }} {{ $errors->has('tierNote') ? 'border-error' : 'border-line focus:border-primary-500' }}" />
                    @error('tierNote')
                        <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                    @else
                        <p class="mt-1.5 text-xs text-fg-subtle">{{ __('settings.services.new.tierNoteHint') }}</p>
                    @enderror
                </div>
            </div>
        @endif

        {{-- Step 3: duration --}}
        @if ($step === 3)
            <div class="space-y-4">
                <x-ui.input type="number" min="5" max="480" step="5" dir="ltr" inputmode="numeric"
                    :label="__('settings.services.new.durationMin')" wire:model="duration"
                    :hint="__('settings.services.new.durationHint')" :error="$errors->first('duration')" />
            </div>
        @endif

        {{-- Step 4: resourcing --}}
        @if ($step === 4)
            <div class="space-y-4">
                <p class="text-sm text-fg-muted">{{ __('settings.services.new.resDesc') }}</p>
                <x-ui.select :label="__('settings.services.new.reqRes')" wire:model="resource"
                    :options="$resourceOptions" :error="$errors->first('resource')" />
                <x-ui.input type="number" min="1" max="99" dir="ltr" inputmode="numeric"
                    :label="__('settings.services.new.capacity')" wire:model="capacity"
                    :hint="__('settings.services.new.capacityHint')" :error="$errors->first('capacity')" />
            </div>
        @endif

        {{-- Step 5: commission --}}
        @if ($step === 5)
            <div class="space-y-4">
                <x-ui.input type="number" min="0" max="100" step="0.5" dir="ltr" inputmode="decimal"
                    :label="__('settings.services.new.commPct')" wire:model="commission" placeholder="0"
                    :hint="__('settings.services.new.commHint')" :error="$errors->first('commission')" />
            </div>
        @endif

        {{-- Step 6: review + media --}}
        @if ($step === 6)
            <div class="space-y-5">
                <p class="text-sm text-fg-muted">{{ __('settings.services.new.reviewHint') }}</p>

                <dl class="divide-y divide-line rounded-xl border border-line">
                    @php
                        $rows = [
                            __('settings.services.new.svcName') => $name !== '' ? $name : __('settings.services.new.notSet'),
                            __('settings.services.new.category') => $category !== '' ? ($categoryOptions[$category] ?? $category) : __('settings.services.new.resNone'),
                            __('settings.services.new.price') => $price !== '' ? \App\Support\Money::format(\App\Support\Money::toMinor((float) $price)) : __('settings.services.new.notSet'),
                            __('settings.services.new.durationMin') => $duration.' '.__('settings.services.min'),
                            __('settings.services.new.reqRes') => ($resourceOptions[$resource] ?? $resource).' × '.$capacity,
                            __('settings.services.new.commPct') => $commission !== '' ? $commission.'%' : __('settings.services.new.notSet'),
                        ];
                    @endphp
                    @foreach ($rows as $label => $value)
                        <div class="flex items-start justify-between gap-4 px-4 py-2.5">
                            <dt class="text-sm text-fg-muted">{{ $label }}</dt>
                            <dd class="text-sm font-medium text-fg text-end">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('settings.services.new.media') }}</label>
                    <label class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-line bg-surface-2 px-4 py-6 text-center transition hover:border-primary-400">
                        <x-icon name="image" class="size-7 text-fg-subtle" />
                        @if ($image)
                            <span class="text-sm font-medium text-fg">{{ $image->getClientOriginalName() }}</span>
                        @else
                            <span class="text-sm font-medium text-fg">{{ __('settings.services.new.uploadMsg1') }}</span>
                            <span class="text-xs text-fg-subtle">{{ __('settings.services.new.uploadMsg2') }}</span>
                        @endif
                        <span class="mt-1 inline-flex items-center rounded-lg border border-line bg-surface px-3 py-1.5 text-xs font-medium text-fg">{{ __('settings.services.new.selectFile') }}</span>
                        <input type="file" wire:model="image" accept="image/*" class="hidden" />
                    </label>
                    @error('image')
                        <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        @endif

        {{-- Footer navigation --}}
        <div class="mt-6 flex items-center justify-between gap-2 border-t border-line pt-5">
            @if ($step > 1)
                <x-ui.button variant="outline" wire:click="back">
                    <x-icon name="chevron-left" class="size-4 rtl:rotate-180" />{{ __('settings.services.new.back') }}
                </x-ui.button>
            @else
                <x-ui.button variant="outline" :href="route('settings.services')" wire:navigate>{{ __('settings.services.new.cancel') }}</x-ui.button>
            @endif

            @if ($step < 6)
                <x-ui.button variant="primary" wire:click="next">
                    {{ __('settings.services.new.next') }}<x-icon name="chevron-right" class="size-4 rtl:rotate-180" />
                </x-ui.button>
            @else
                <x-ui.button variant="primary" icon="check" wire:click="save" loadingTarget="save">
                    {{ __('settings.services.new.save') }}
                </x-ui.button>
            @endif
        </div>
    </x-ui.card>
</div>
