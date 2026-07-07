@props(['align' => 'end', 'width' => 'w-44', 'ariaLabel' => null, 'icon' => 'more-vertical'])

{{--
    Row-action / overflow menu. Replaces the hand-rolled `x-data="{ o:false }"`
    block duplicated across list views. Put <x-ui.dropdown-item> children in the slot.
    Clicking anywhere in the menu closes it (event bubbles to the container).
--}}
<div x-data="{ o: false }" @click.outside="o = false" class="relative inline-block">
    <button type="button" @click="o = !o" aria-label="{{ $ariaLabel ?? __('common.actions') }}"
        class="grid size-8 place-items-center rounded-lg text-fg-subtle hover:bg-surface-3">
        <x-icon :name="$icon" class="size-4" />
    </button>
    <div x-show="o" x-cloak @click="o = false"
        class="absolute {{ $align === 'end' ? 'end-0' : 'start-0' }} z-20 mt-1 {{ $width }} overflow-hidden rounded-lg border border-line bg-surface py-1 shadow-lg">
        {{ $slot }}
    </div>
</div>
