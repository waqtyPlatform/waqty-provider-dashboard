{{--
    Loading placeholder. Render inside a `wire:loading` block so lists/tables
    don't pop in. Pass size classes: <x-ui.skeleton class="h-4 w-32" />.
--}}
<div {{ $attributes->merge(['class' => 'animate-pulse rounded bg-surface-3']) }}></div>
