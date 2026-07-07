@props(['name' => '', 'size' => 'size-9'])

@php
    $initials = collect(explode(' ', trim((string) $name)))
        ->filter()
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
        ->take(2)->implode('');
@endphp

<span {{ $attributes->merge(['class' => 'inline-grid shrink-0 place-items-center rounded-full bg-primary-100 text-sm font-semibold text-primary-700 '.$size]) }}>
    {{ $initials ?: '—' }}
</span>
