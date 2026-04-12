@props([
    'labels' => [],
    'datasets' => [],
    'type' => 'line',
    'target' => null,
])

@php
    $width = 640;
    $height = 220;
    $padding = ['top' => 16, 'right' => 16, 'bottom' => 32, 'left' => 48];
    $plotWidth = $width - $padding['left'] - $padding['right'];
    $plotHeight = $height - $padding['top'] - $padding['bottom'];

    $allValues = [];
    foreach ($datasets as $d) {
        foreach ($d['data'] ?? [] as $v) {
            $allValues[] = $v;
        }
    }
    if ($target !== null) {
        $allValues[] = $target;
    }
    $maxVal = count($allValues) ? max($allValues) : 1;
    $minVal = count($allValues) ? min(0, min($allValues)) : 0;
    $range = max(1, $maxVal - $minVal);

    $pointCount = count($labels);

    // Bar charts centre bars inside equal-width cells (plotWidth / N);
    // line charts place points on the plot edges (plotWidth / (N - 1)).
    if ($type === 'bar') {
        $stepX = $pointCount > 0 ? $plotWidth / $pointCount : $plotWidth;
        $xs = [];
        for ($i = 0; $i < $pointCount; $i++) {
            $xs[] = $padding['left'] + $stepX * ($i + 0.5);
        }
        $barWidth = max(4, $stepX * 0.6);
    } else {
        $stepX = $pointCount > 1 ? $plotWidth / ($pointCount - 1) : $plotWidth;
        $xs = [];
        for ($i = 0; $i < $pointCount; $i++) {
            $xs[] = $padding['left'] + $stepX * $i;
        }
        $barWidth = 0;
    }

    $scaleY = fn ($v) => $padding['top'] + $plotHeight - (($v - $minVal) / $range) * $plotHeight;
    $baseY = $scaleY($minVal);
    $targetY = $target !== null ? $scaleY($target) : null;
@endphp

<div class="card trend-chart">
    <svg viewBox="0 0 {{ $width }} {{ $height }}" class="trend-chart-svg">
        @for ($i = 0; $i <= 4; $i++)
            @php $y = $padding['top'] + ($plotHeight / 4) * $i; @endphp
            <line x1="{{ $padding['left'] }}" y1="{{ $y }}" x2="{{ $width - $padding['right'] }}" y2="{{ $y }}" stroke="var(--border)" stroke-dasharray="2 3" />
        @endfor

        @if ($targetY !== null)
            <line x1="{{ $padding['left'] }}" y1="{{ $targetY }}" x2="{{ $width - $padding['right'] }}" y2="{{ $targetY }}" stroke="var(--error)" stroke-width="1.5" stroke-dasharray="4 4" />
            <text x="{{ $width - $padding['right'] }}" y="{{ $targetY - 4 }}" text-anchor="end" fill="var(--error)" font-size="10">目標 {{ $target }}</text>
        @endif

        @foreach ($datasets as $ds)
            @php
                $color = $ds['color'] ?? '#1ed760';
                $data = $ds['data'] ?? [];
                $ys = array_map($scaleY, $data);
            @endphp

            @if ($type === 'bar')
                @foreach ($data as $j => $val)
                    <rect
                        x="{{ $xs[$j] - $barWidth / 2 }}"
                        y="{{ $ys[$j] }}"
                        width="{{ $barWidth }}"
                        height="{{ $baseY - $ys[$j] }}"
                        fill="{{ $color }}"
                        rx="2"
                    />
                @endforeach
            @else
                @php
                    $path = '';
                    foreach ($ys as $j => $y) {
                        $path .= ($j === 0 ? "M {$xs[$j]},{$y}" : " L {$xs[$j]},{$y}");
                    }
                @endphp
                <path d="{{ $path }}" fill="none" stroke="{{ $color }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                @foreach ($ys as $j => $y)
                    <circle cx="{{ $xs[$j] }}" cy="{{ $y }}" r="3" fill="{{ $color }}" />
                @endforeach
            @endif
        @endforeach

        @foreach ($labels as $j => $label)
            <text x="{{ $xs[$j] }}" y="{{ $height - 12 }}" text-anchor="middle" fill="var(--text-tertiary)" font-size="9">{{ $label }}</text>
        @endforeach

        @for ($i = 0; $i <= 4; $i++)
            @php
                $y = $padding['top'] + ($plotHeight / 4) * $i;
                $val = $maxVal - ($range / 4) * $i;
            @endphp
            <text x="{{ $padding['left'] - 8 }}" y="{{ $y + 4 }}" text-anchor="end" fill="var(--text-tertiary)" font-size="9">{{ number_format($val) }}</text>
        @endfor
    </svg>

    <div class="row-sm trend-chart-legend">
        @foreach ($datasets as $ds)
            <div class="row-sm items-center trend-chart-legend-item">
                <span class="trend-chart-swatch" style="background: {{ $ds['color'] ?? '#1ed760' }};"></span>
                <span class="text-[12px] text-[var(--text-secondary)]">{{ $ds['label'] ?? '—' }}</span>
            </div>
        @endforeach
    </div>
</div>
