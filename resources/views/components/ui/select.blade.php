@props(['label' => null, 'options' => [], 'error' => null, 'placeholder' => null, 'required' => false])

@php $id = $attributes->get('id') ?? 'f_'.\Illuminate\Support\Str::random(6); @endphp

<div>
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-fg">{{ $label }}@if ($required)<span class="text-error"> *</span>@endif</label>
    @endif
    <select
        id="{{ $id }}"
        @if ($required) aria-required="true" @endif
        {{ $attributes->except('id')->merge([
            'class' => 'w-full rounded-lg border bg-surface px-3 py-2.5 text-sm text-fg focus:outline-none focus:ring-2 focus:ring-primary-500/20 '
                .($error ? 'border-error' : 'border-line focus:border-primary-500'),
        ]) }}
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $value => $text)
            <option value="{{ $value }}">{{ $text }}</option>
        @endforeach
        {{ $slot }}
    </select>
    @if ($error)
        <p class="mt-1.5 text-xs text-error">{{ $error }}</p>
    @endif
</div>
