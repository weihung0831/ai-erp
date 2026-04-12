@props([
    'variant' => 'icon',
])

@php
    $classes = match ($variant) {
        'labeled' => 'btn btn-text',
        default => 'theme-toggle',
    };
@endphp

<button
    type="button"
    {{ $attributes->merge(['class' => $classes]) }}
    @click="theme = (theme === 'dark' ? 'light' : 'dark'); localStorage.setItem('theme', theme); document.documentElement.setAttribute('data-theme', theme)"
    :title="theme === 'dark' ? '切換亮色模式' : '切換暗色模式'"
>
    @if($variant === 'labeled')
        <span x-text="theme === 'dark' ? '☀ 亮色' : '☾ 暗色'"></span>
    @else
        <span x-text="theme === 'dark' ? '☀' : '☾'"></span>
    @endif
</button>
