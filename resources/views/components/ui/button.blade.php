@props([
    'variant' => 'primary',
    'size' => 'md',
    'disabled' => false,
    'loading' => false,
    'type' => 'button',
])

@php
    $variantClass = match ($variant) {
        'secondary' => 'btn-secondary',
        'text'      => 'btn-text',
        'danger'    => 'btn-danger',
        default     => 'btn-primary',
    };

    $sizeClass = match ($size) {
        'sm' => 'btn-sm',
        'lg' => 'btn-lg',
        default => '',
    };
@endphp

<button
    {{ $attributes->merge(['class' => "btn {$variantClass} {$sizeClass}", 'type' => $type]) }}
    @disabled($disabled || $loading)
>
    @if ($loading)
        <svg class="animate-spin" width="16" height="16" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="30 60" stroke-linecap="round" opacity="0.6"/>
        </svg>
    @endif
    {{ $slot }}
</button>
