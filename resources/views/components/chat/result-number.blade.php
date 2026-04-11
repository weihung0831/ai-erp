@props([
    'value',
    'label',
    'format' => 'currency',
    'compare' => null,
])

@php
    $num = (float) $value;
    $formatted = match ($format) {
        'currency' => 'NT$' . number_format($num),
        'percent'  => number_format($num * 100, 1) . '%',
        default    => number_format($num),
    };

    $delta = null;
    if ($compare !== null && (float) $compare !== 0.0) {
        $delta = ($num - (float) $compare) / (float) $compare;
    }
@endphp

<div class="stack-xs result-number">
    <span class="stat-label">{{ $label }}</span>
    <span class="stat-value">{{ $formatted }}</span>
    @if ($delta !== null)
        <span class="{{ $delta >= 0 ? 'stat-trend-up' : 'stat-trend-down' }}">
            {{ $delta >= 0 ? '▲' : '▼' }} {{ number_format(abs($delta) * 100, 1) }}%
        </span>
    @endif
</div>
