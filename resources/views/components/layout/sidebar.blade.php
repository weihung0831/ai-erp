@props([
    'items' => [],
    'active' => null,
    'brand' => 'AI ERP',
])

<aside
    {{ $attributes->merge(['class' => 'sidebar']) }}
    role="navigation"
    aria-label="主要導覽"
>
    <div class="sidebar-logo">{{ $brand }}</div>

    <nav class="stack-xs">
        @foreach ($items as $item)
            <a href="{{ $item['url'] ?? '#' }}"
               class="sidebar-item @if ($active === ($item['url'] ?? null)) active @endif">
                @if (isset($item['icon']))
                    <span class="inline-flex w-5 justify-center text-base" aria-hidden="true">{{ $item['icon'] }}</span>
                @endif
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    @isset($footer)
        {{ $footer }}
    @endisset
</aside>
