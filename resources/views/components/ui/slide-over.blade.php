@props(['wire', 'title' => null, 'maxWidth' => 'max-w-md'])

{{--
    Right-side drawer bound to a Livewire boolean via @entangle.
    Usage: <x-ui.slide-over wire="showForm" :title="...">  <form>…</form>  </x-ui.slide-over>
--}}
<div x-data="{ open: @entangle($wire) }" x-show="open" x-cloak @keydown.escape.window="open = false" class="fixed inset-0 z-[1300]">
    <div class="absolute inset-0 bg-overlay" @click="open = false"></div>
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-x-full rtl:-translate-x-full"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-end="translate-x-full rtl:-translate-x-full"
        class="absolute inset-y-0 end-0 flex w-full {{ $maxWidth }} flex-col bg-surface shadow-xl"
    >
        @if ($title !== null)
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <h2 class="text-lg font-semibold text-fg">{{ $title }}</h2>
                <button type="button" @click="open = false" class="grid size-8 place-items-center rounded-lg text-fg-subtle hover:bg-surface-2"><x-icon name="x" /></button>
            </div>
        @endif
        {{ $slot }}
    </div>
</div>
