@props([
    'current',
    'total',
])

@php
    $pct = $total > 0 ? min(100, ($current / $total) * 100) : 0;
@endphp

<div class="stack-xs">
    <div class="row-sm items-center justify-between">
        <span class="text-[14px] text-[var(--text-secondary)]">步驟 {{ $current }} / {{ $total }}</span>
        <span class="text-[14px] text-[var(--text-tertiary)]">{{ round($pct) }}%</span>
    </div>

    <div class="progress-track onboarding-progress-track">
        <div class="progress-fill" style="width: {{ $pct }}%"></div>
    </div>

    <div class="row-sm items-center justify-between onboarding-progress-dots">
        @for ($i = 1; $i <= $total; $i++)
            <span class="step-dot {{ $i <= $current ? 'is-done' : '' }}">{{ $i }}</span>
        @endfor
    </div>
</div>
