@props(['on' => false, 'disabled' => false, 'size' => 'md'])

@php
    $track = $size === 'sm' ? 'h-5 w-9' : 'h-6 w-11';
    $knob = $size === 'sm' ? 'size-3.5' : 'size-4.5';
    $shift = $size === 'sm'
        ? ($on ? 'translate-x-4 rtl:-translate-x-4' : 'translate-x-0.5 rtl:-translate-x-0.5')
        : ($on ? 'translate-x-5 rtl:-translate-x-5' : 'translate-x-0.5 rtl:-translate-x-0.5');
@endphp

<button
    type="button"
    role="switch"
    aria-checked="{{ $on ? 'true' : 'false' }}"
    @disabled($disabled)
    {{ $attributes->merge([
        'class' => 'relative inline-flex '.$track.' shrink-0 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500/30 disabled:cursor-not-allowed disabled:opacity-50 '
            .($on ? 'bg-primary-500' : 'bg-surface-3'),
    ]) }}
>
    <span class="{{ $knob }} {{ $shift }} inline-block transform rounded-full bg-white shadow transition-transform"></span>
</button>
