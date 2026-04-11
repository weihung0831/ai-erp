@props([
    'label',
    'value',
    'trend' => null,
])

<div class="stat-card">
    <span class="stat-label">{{ $label }}</span>
    <span class="stat-value">{{ $value }}</span>
    @if ($trend !== null)
        @php
            $isUp = $trend >= 0;
            $arrow = $isUp ? '▲' : '▼';
            $pct = number_format(abs($trend) * 100, 1);
        @endphp
        <span class="{{ $isUp ? 'stat-trend-up' : 'stat-trend-down' }}">
            {{ $arrow }} {{ $pct }}%
        </span>
    @endif
</div>
