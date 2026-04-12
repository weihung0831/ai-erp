@props([
    'label',
    'value',
    'trend' => null,
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'dashboard-stat-card']) }}>
    @if ($icon)
        <span class="dashboard-stat-icon">{!! $icon !!}</span>
    @endif
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
