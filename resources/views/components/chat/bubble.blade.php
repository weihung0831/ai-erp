@props([
    'type' => 'ai',
    'confidence' => null,
    'sql' => null,
])

@php
    [$bubbleClass, $wrapperAlign] = match ($type) {
        'user'   => ['bubble bubble-user',   'flex justify-end'],
        'system' => ['bubble bubble-system', 'flex justify-center'],
        default  => ['bubble bubble-ai',     'flex justify-start'],
    };
@endphp

<div class="{{ $wrapperAlign }}">
    <div {{ $attributes->merge(['class' => $bubbleClass]) }}>
        @if ($type === 'ai' && $confidence !== null)
            <div class="bubble-confidence">
                <x-chat.confidence :score="$confidence" />
            </div>
        @endif

        {{ $slot }}

        @if ($type === 'ai' && $sql && $confidence !== null && $confidence < 0.95)
            <details class="bubble-sql">
                <summary>檢視產生的 SQL</summary>
                <pre>{{ $sql }}</pre>
            </details>
        @endif
    </div>
</div>
