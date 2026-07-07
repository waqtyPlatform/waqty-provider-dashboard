@props(['title', 'subtitle' => null])

<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div class="min-w-0">
        <h1 class="text-xl font-semibold text-fg">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-0.5 text-sm text-fg-muted">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
