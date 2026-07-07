@props(['padding' => 'p-6'])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-line bg-surface '.$padding]) }}>
    {{ $slot }}
</div>
