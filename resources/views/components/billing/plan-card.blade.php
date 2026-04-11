@props([
    'name',
    'price',
    'features' => [],
    'recommended' => false,
    'current' => false,
])

<div class="card stack-md plan-card {{ $recommended ? 'is-selected' : '' }}">
    @if ($recommended)
        <x-ui.badge variant="warning">推薦方案</x-ui.badge>
    @endif

    <div class="stack-xs">
        <h3 class="h-card">{{ $name }}</h3>
        <span class="plan-card-price">{{ $price }}</span>
    </div>

    <ul class="stack-sm plan-card-features">
        @foreach ($features as $feature)
            <li class="row-sm items-start">
                <span class="plan-card-check" aria-hidden="true">✓</span>
                <span class="text-[14px] text-[var(--text-secondary)]">{{ $feature }}</span>
            </li>
        @endforeach
    </ul>

    @if ($current)
        <x-ui.button variant="secondary" disabled class="w-full justify-center">目前方案</x-ui.button>
    @else
        <x-ui.button variant="primary" class="w-full justify-center">選擇 {{ $name }}</x-ui.button>
    @endif
</div>
