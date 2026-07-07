@props(['page' => 1, 'perPage' => 15, 'total' => 0, 'field' => 'currentPage'])

@php
    $last = max(1, (int) ceil($total / max(1, $perPage)));
    $from = $total ? (($page - 1) * $perPage) + 1 : 0;
    $to = min($total, $page * $perPage);
@endphp

<div class="flex items-center justify-between gap-3 border-t border-line px-4 py-3 text-sm text-fg-muted">
    <span class="tabular-nums">{{ $from }}–{{ $to }} {{ __('common.of') }} {{ $total }}</span>
    <div class="flex items-center gap-1">
        <button wire:click="$set('{{ $field }}', {{ max(1, $page - 1) }})" @disabled($page <= 1)
            class="grid size-8 place-items-center rounded-lg border border-line hover:bg-surface-2 disabled:opacity-40 rtl:rotate-180">
            <x-icon name="chevron-left" class="size-4" />
        </button>
        <span class="px-2 tabular-nums">{{ $page }} / {{ $last }}</span>
        <button wire:click="$set('{{ $field }}', {{ min($last, $page + 1) }})" @disabled($page >= $last)
            class="grid size-8 place-items-center rounded-lg border border-line hover:bg-surface-2 disabled:opacity-40 rtl:rotate-180">
            <x-icon name="chevron-right" class="size-4" />
        </button>
    </div>
</div>
