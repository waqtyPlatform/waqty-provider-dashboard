@props(['wire', 'maxWidth' => 'max-w-sm'])

{{-- Centered dialog bound to a Livewire boolean via @entangle. --}}
<div x-data="{ open: @entangle($wire) }" x-show="open" x-cloak @keydown.escape.window="open = false" class="fixed inset-0 z-[1300] grid place-items-center p-4">
    <div class="absolute inset-0 bg-overlay" @click="open = false"></div>
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="scale-95 opacity-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-end="scale-95 opacity-0"
        class="relative w-full {{ $maxWidth }} rounded-2xl border border-line bg-surface p-6 shadow-xl"
    >
        {{ $slot }}
    </div>
</div>
