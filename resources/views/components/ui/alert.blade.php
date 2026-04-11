@props([
    'type' => 'info',
    'dismissible' => true,
])

@php
    $class = match ($type) {
        'success' => 'alert-success',
        'warning' => 'alert-warning',
        'error'   => 'alert-error',
        default   => 'alert-info',
    };

    $icon = match ($type) {
        'success' => '✓',
        'warning' => '!',
        'error'   => '✕',
        default   => 'i',
    };
@endphp

@if ($dismissible)
    <div
        x-data="{ show: true }"
        x-show="show"
        x-cloak
        {{ $attributes->merge(['class' => "alert {$class}"]) }}
    >
        <span aria-hidden="true" class="font-bold">{{ $icon }}</span>
        <div class="flex-1">{{ $slot }}</div>
        <button type="button" @click="show = false" class="alert-close" aria-label="關閉">&times;</button>
    </div>
@else
    <div {{ $attributes->merge(['class' => "alert {$class}"]) }}>
        <span aria-hidden="true" class="font-bold">{{ $icon }}</span>
        <div class="flex-1">{{ $slot }}</div>
    </div>
@endif
