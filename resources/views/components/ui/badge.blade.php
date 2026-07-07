@props(['color' => 'neutral'])

@php
    $map = [
        'success' => 'bg-success-light text-success',
        'error' => 'bg-error-light text-error',
        'destructive' => 'bg-error-light text-error',
        'warning' => 'bg-warning-light text-warning',
        'info' => 'bg-info-light text-info',
        'neutral' => 'bg-surface-3 text-fg-muted',
        'primary' => 'bg-primary-50 text-primary-700',
        'purple' => 'bg-status-arrived-bg text-status-arrived',
        'amber' => 'bg-warning-light text-warning',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium '.($map[$color] ?? $map['neutral'])]) }}>
    {{ $slot }}
</span>
