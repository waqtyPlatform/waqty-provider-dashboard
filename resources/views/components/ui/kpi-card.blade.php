@props([
    'label',
    'value',
    'icon' => null,
    'iconClass' => 'bg-primary-50 text-primary-600',
    'trend' => null,
    'trendUp' => true,
])

<div {{ $attributes->merge(['class' => 'flex items-center gap-4 rounded-xl border border-line bg-surface p-4']) }}>
    @if ($icon)
        <span class="grid size-11 shrink-0 place-items-center rounded-xl {{ $iconClass }}">
            <x-icon :name="$icon" class="size-5" />
        </span>
    @endif
    <div class="min-w-0">
        <p class="truncate text-sm text-fg-muted">{{ $label }}</p>
        <p class="text-xl font-semibold tabular-nums text-fg">{{ $value }}</p>
        @if ($trend)
            <p class="text-xs {{ $trendUp ? 'text-success' : 'text-error' }}">{{ $trend }}</p>
        @endif
    </div>
</div>
