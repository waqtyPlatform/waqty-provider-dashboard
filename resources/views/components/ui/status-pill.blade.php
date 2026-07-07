@props(['status' => '', 'label' => null])

@php
    // Port of STATUS_MAP (src/components/ui/index.tsx): status -> tone.
    $map = [
        'completed' => 'success', 'confirmed' => 'info', 'work-done' => 'info',
        'active' => 'success', 'paid' => 'success',
        'in_progress' => 'purple', 'arrived' => 'purple',
        'unconfirmed' => 'warning', 'pending' => 'warning', 'waiting-pay' => 'warning', 'partial' => 'warning',
        'cancelled' => 'error', 'failed' => 'error', 'unpaid' => 'error',
        'no_show' => 'neutral', 'no-show' => 'neutral', 'disabled' => 'neutral', 'inactive' => 'neutral',
    ];
    $key = strtolower((string) $status);
    $tone = $map[$key] ?? 'neutral';
    // Prefer an explicit label; otherwise a localized status.* key; never leak English title-case.
    $translated = __('status.'.$key);
    $text = $label ?? ($translated !== 'status.'.$key
        ? $translated
        : \Illuminate\Support\Str::of((string) $status)->replace(['_', '-'], ' ')->title());
@endphp

<x-ui.badge :color="$tone">{{ $text }}</x-ui.badge>
