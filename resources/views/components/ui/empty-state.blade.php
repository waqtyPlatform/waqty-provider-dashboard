@props(['title' => null, 'description' => null, 'icon' => 'inbox'])

<div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-line bg-surface p-10 text-center">
    <div class="mb-3 grid size-12 place-items-center rounded-full bg-surface-2 text-fg-subtle">
        <x-icon :name="$icon" class="size-6" />
    </div>
    <p class="text-sm font-semibold text-fg">{{ $title ?? __('empty.title') }}</p>
    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-fg-muted">{{ $description }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
