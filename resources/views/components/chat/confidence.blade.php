@props(['score'])

@php
    $score = (float) $score;
    if ($score > 0.95) {
        $class = 'confidence-high';
        $label = '高信心';
    } elseif ($score >= 0.70) {
        $class = 'confidence-mid';
        $label = '中信心';
    } else {
        $class = 'confidence-low';
        $label = '低信心';
    }
    $pct = round($score * 100);
@endphp

<span {{ $attributes->merge(['class' => "confidence {$class}"]) }}>
    {{ $label }} {{ $pct }}%
</span>
