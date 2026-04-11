@props([
    'text',
    'position' => 'top',
])

@php
    $posClass = "tooltip-{$position}";
@endphp

<span {{ $attributes->merge(['class' => 'tooltip-wrap']) }}>
    {{ $slot }}
    <span class="tooltip-bubble {{ $posClass }}" role="tooltip">{{ $text }}</span>
</span>
