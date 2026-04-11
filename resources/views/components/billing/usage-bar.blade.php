@props([
    'label',
    'used',
    'limit',
    'warning' => 0.8,
])

@php
    $ratio = $limit > 0 ? min(1, $used / $limit) : 0;
    $pct = $ratio * 100;
    if ($ratio >= 0.95) {
        $state = 'is-danger';
        $textColor = 'text-[var(--error)]';
    } elseif ($ratio >= $warning) {
        $state = 'is-warning';
        $textColor = 'text-[var(--brand)]';
    } else {
        $state = 'is-ok';
        $textColor = 'text-[var(--success)]';
    }
@endphp

<div class="stack-xs">
    <div class="row-sm items-center justify-between">
        <span class="text-[14px] text-[var(--text-secondary)]">{{ $label }}</span>
        <span class="text-[14px] tabular font-medium {{ $textColor }}">
            {{ number_format($used) }} / {{ number_format($limit) }}
        </span>
    </div>
    <div class="progress-track usage-bar-track">
        <div class="progress-fill {{ $state }}" style="width: {{ $pct }}%"></div>
    </div>
</div>
