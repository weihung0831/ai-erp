@props(['variant' => 'default'])

@php
    $class = match ($variant) {
        'success' => 'badge-success',
        'warning' => 'badge-warning',
        'danger'  => 'badge-danger',
        default   => 'badge-default',
    };
@endphp

<span {{ $attributes->merge(['class' => "badge {$class}"]) }}>
    {{ $slot }}
</span>
