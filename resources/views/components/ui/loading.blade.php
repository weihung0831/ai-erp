@props([
    'type' => 'block',
    'height' => '16px',
    'width' => '100%',
])

@php
    $class = match ($type) {
        'line'   => 'skeleton skeleton-line',
        'circle' => 'skeleton skeleton-circle',
        default  => 'skeleton',
    };

    $style = "height: {$height}; width: {$width};";
    if ($type === 'circle') {
        $style .= " aspect-ratio: 1 / 1;";
    }
@endphp

<span
    {{ $attributes->merge(['class' => $class]) }}
    style="{{ $style }}"
    role="status"
    aria-label="載入中"
></span>
