@php
    $styles = [
        'success' => ['bg' => 'bg-success-light', 'text' => 'text-success', 'icon' => 'calendar-check'],
        'error' => ['bg' => 'bg-error-light', 'text' => 'text-error', 'icon' => 'alert-triangle'],
        'warning' => ['bg' => 'bg-warning-light', 'text' => 'text-warning', 'icon' => 'alert-triangle'],
        'info' => ['bg' => 'bg-info-light', 'text' => 'text-info', 'icon' => 'bell'],
    ];
@endphp

<div class="pointer-events-none fixed inset-x-0 bottom-0 z-[1400] flex flex-col items-end gap-2 p-4 sm:inset-x-auto sm:end-0">
    @foreach ($toasts as $toast)
        @php $s = $styles[$toast['type']] ?? $styles['info']; @endphp
        <div
            wire:key="toast-{{ $toast['id'] }}"
            x-data
            x-init="@if ($toast['type'] !== 'error') setTimeout(() => $wire.dismiss('{{ $toast['id'] }}'), 5000) @endif"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-2 opacity-0"
            class="animate-slide-up pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border border-line bg-surface p-3.5 shadow-lg"
        >
            <span class="grid size-8 shrink-0 place-items-center rounded-lg {{ $s['bg'] }} {{ $s['text'] }}">
                <x-icon :name="$s['icon']" class="size-4" />
            </span>
            <p class="flex-1 pt-0.5 text-sm text-fg">{{ $toast['message'] }}</p>
            <button wire:click="dismiss('{{ $toast['id'] }}')" class="text-fg-subtle hover:text-fg">
                <x-icon name="x" class="size-4" />
            </button>
        </div>
    @endforeach
</div>
