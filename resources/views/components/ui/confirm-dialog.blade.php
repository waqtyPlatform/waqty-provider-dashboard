@props([
    'wire',
    'title',
    'description' => null,
    'action',
    'actionLabel' => null,
    'variant' => 'destructive',
    'icon' => 'alert-triangle',
])

{{--
    Confirmation dialog built on <x-ui.modal>. The optional slot holds extra
    content (e.g. a reason textarea) rendered above the buttons.
--}}
<x-ui.modal :wire="$wire">
    <div class="mb-3 grid size-11 place-items-center rounded-full bg-error-light text-error"><x-icon :name="$icon" class="size-5" /></div>
    <h3 class="text-lg font-semibold text-fg">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1 text-sm text-fg-muted">{{ $description }}</p>
    @endif
    {{ $slot }}
    <div class="mt-5 flex items-center justify-end gap-2">
        <x-ui.button type="button" variant="secondary" @click="open = false">{{ __('common.cancel') }}</x-ui.button>
        <x-ui.button type="button" variant="{{ $variant }}" wire:click="{{ $action }}" wire:loading.attr="disabled">{{ $actionLabel ?? __('common.delete') }}</x-ui.button>
    </div>
</x-ui.modal>
