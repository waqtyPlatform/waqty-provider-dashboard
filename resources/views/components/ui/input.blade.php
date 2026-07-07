@props(['label' => null, 'hint' => null, 'error' => null, 'required' => false])

@php $id = $attributes->get('id') ?? 'f_'.\Illuminate\Support\Str::random(6); @endphp

<div>
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-fg">{{ $label }}@if ($required)<span class="text-error"> *</span>@endif</label>
    @endif
    <input
        id="{{ $id }}"
        @if ($required) aria-required="true" @endif
        {{ $attributes->except('id')->merge([
            'class' => 'w-full rounded-lg border bg-surface px-3.5 py-2.5 text-sm text-fg placeholder:text-fg-subtle focus:outline-none focus:ring-2 focus:ring-primary-500/20 '
                .($error ? 'border-error' : 'border-line focus:border-primary-500'),
        ]) }}
    >
    @if ($error)
        <p class="mt-1.5 text-xs text-error">{{ $error }}</p>
    @elseif ($hint)
        <p class="mt-1.5 text-xs text-fg-subtle">{{ $hint }}</p>
    @endif
</div>
