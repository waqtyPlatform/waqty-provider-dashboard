@props(['type' => 'info', 'icon' => null])

@php
    // type => [wrapper classes, default icon]
    $styles = [
        'info' => ['border-info/30 bg-info-light text-info', 'sparkles'],
        'warning' => ['border-warning/30 bg-warning-light text-warning', 'alert-triangle'],
        'success' => ['border-success/30 bg-success-light text-success', 'check'],
        'error' => ['border-error/30 bg-error-light text-error', 'alert-triangle'],
    ];
    [$cls, $defaultIcon] = $styles[$type] ?? $styles['info'];
    $resolvedIcon = $icon ?? $defaultIcon;
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-2 rounded-lg border px-3.5 py-2 text-sm '.$cls]) }}>
    <x-icon :name="$resolvedIcon" class="size-4 shrink-0" />
    <span>{{ $slot }}</span>
</div>
