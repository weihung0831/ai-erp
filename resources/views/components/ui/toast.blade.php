@props([
    'type' => 'success',
    'message',
    'duration' => 3000,
])

@php
    $icon = match ($type) {
        'error' => '✕',
        'info'  => 'i',
        default => '✓',
    };
    $typeClass = match ($type) {
        'error' => 'toast-error',
        default => 'toast-success',
    };
@endphp

<template x-teleport="body">
    <div
        x-data="{ show: true }"
        x-show="show"
        x-init="setTimeout(() => show = false, {{ $duration }})"
        x-cloak
        {{ $attributes->merge(['class' => "toast {$typeClass}"]) }}
        role="status"
    >
        <span class="toast-icon font-bold" aria-hidden="true">{{ $icon }}</span>
        <span>{{ $message }}</span>
    </div>
</template>
