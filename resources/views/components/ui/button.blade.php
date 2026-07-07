@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'loading' => false,
    'loadingTarget' => null,
    'icon' => null,
    'href' => null,
])

@php
    $variants = [
        'primary' => 'bg-primary-500 text-white hover:bg-primary-600 focus:ring-primary-500/40',
        'secondary' => 'border border-line bg-surface-2 text-fg hover:bg-surface-3 focus:ring-primary-500/20',
        'ghost' => 'text-fg-muted hover:bg-surface-2 focus:ring-primary-500/20',
        'destructive' => 'bg-error text-white hover:bg-error-hover focus:ring-error/30',
        'outline' => 'border border-line text-fg hover:bg-surface-2 focus:ring-primary-500/20',
    ];
    $sizes = [
        'sm' => 'px-2.5 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-2.5 text-base',
    ];
    $classes = 'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:opacity-60 '
        .($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-icon :name="$icon" class="size-4" />@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" @disabled($loading)
        @if ($loadingTarget) wire:loading.attr="disabled" wire:target="{{ $loadingTarget }}" @endif
        {{ $attributes->merge(['class' => $classes]) }}>
        @if ($loadingTarget)
            <svg wire:loading wire:target="{{ $loadingTarget }}" class="size-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"/></svg>
            @if ($icon)<x-icon :name="$icon" class="size-4" wire:loading.remove wire:target="{{ $loadingTarget }}" />@endif
        @elseif ($loading)
            <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"/></svg>
        @elseif ($icon)
            <x-icon :name="$icon" class="size-4" />
        @endif
        {{ $slot }}
    </button>
@endif
