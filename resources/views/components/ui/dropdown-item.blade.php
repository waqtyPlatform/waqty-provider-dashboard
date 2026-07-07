@props(['icon' => null, 'destructive' => false])

<button type="button" {{ $attributes->merge([
    'class' => 'flex w-full items-center gap-2 px-3 py-2 text-start text-sm '
        .($destructive ? 'text-error hover:bg-error-light' : 'text-fg hover:bg-surface-2'),
]) }}>
    @if ($icon)<x-icon :name="$icon" class="size-4" />@endif
    {{ $slot }}
</button>
