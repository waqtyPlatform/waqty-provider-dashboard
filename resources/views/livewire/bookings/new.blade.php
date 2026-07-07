@php
    use App\Support\Money;
    $services = $this->services();
    $employees = $this->employees();
    $selected = collect($services)->firstWhere('uuid', $form_service);
@endphp

<div class="mx-auto max-w-3xl p-6">
    <a href="{{ route('bookings') }}" wire:navigate class="mb-4 inline-flex items-center gap-1.5 text-sm text-fg-muted hover:text-fg">
        <x-icon name="chevron-left" class="size-4 rtl:rotate-180" />{{ __('sidebar.calendar') }}
    </a>

    <x-ui.page-header :title="__('dash.newBooking')" :subtitle="__('sidebar.bookings')" />

    <form wire:submit="save" class="mt-2 space-y-5">
        <x-ui.card>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="nb-service" class="mb-1.5 block text-sm font-medium text-fg">{{ __('sales.lblServices') }}</label>
                    <select id="nb-service" wire:model.live="form_service" class="w-full rounded-lg border bg-surface px-3 py-2.5 text-sm text-fg focus:outline-none focus:ring-2 focus:ring-primary-500/20 {{ $errors->has('form_service') ? 'border-error' : 'border-line focus:border-primary-500' }}">
                        <option value="">— {{ __('sales.lblServices') }} —</option>
                        @foreach ($services as $s)
                            <option value="{{ $s->uuid }}">{{ $s->displayName() }} · {{ $s->estimated_duration_minutes ?? 30 }} {{ __('sales.minutesShort') }}@if ($s->price) · {{ Money::format($s->price) }}@endif</option>
                        @endforeach
                    </select>
                    @error('form_service') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                </div>

                <x-ui.select :label="$provider->terminology()['staff']" wire:model="form_employee" :placeholder="'— '.$provider->terminology()['staff'].' —'" :options="collect($employees)->pluck('name', 'uuid')->toArray()" />

                <div class="grid grid-cols-2 gap-3">
                    <x-ui.input type="date" :label="__('sales.lblDate')" wire:model="form_date" :error="$errors->first('form_date')" />
                    <div>
                        <label for="nb-time" class="mb-1.5 block text-sm font-medium text-fg">{{ __('sales.lblTime') }}</label>
                        <select id="nb-time" wire:model="form_time" class="w-full rounded-lg border border-line bg-surface px-3 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none">
                            @foreach ($this->timeOptions() as $t)
                                <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <h2 class="mb-4 font-semibold text-fg">{{ $provider->terminology()['customer'] }}</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input :label="__('customers.fullName')" wire:model="form_client_name" :error="$errors->first('form_client_name')" />
                <x-ui.input :label="__('employees.phoneOption')" wire:model="form_client_phone" dir="ltr" :error="$errors->first('form_client_phone')" />
            </div>
            <div class="mt-4">
                <label class="mb-1.5 block text-sm font-medium text-fg">{{ __('common.notes') }}</label>
                <textarea wire:model="form_notes" rows="2" class="w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-fg focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"></textarea>
            </div>
        </x-ui.card>

        {{-- Summary + actions --}}
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line bg-surface-2 px-5 py-4">
            <div>
                <p class="text-xs text-fg-subtle">{{ __('dash.total') }}</p>
                <p class="text-xl font-semibold text-primary-600">{{ $selected && $selected->price ? Money::format($selected->price) : '—' }}</p>
            </div>
            <div class="flex items-center gap-2">
                <x-ui.button href="{{ route('bookings') }}" wire:navigate variant="secondary">{{ __('common.cancel') }}</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save" icon="calendar-check">{{ __('dash.newBooking') }}</x-ui.button>
            </div>
        </div>
    </form>
</div>
