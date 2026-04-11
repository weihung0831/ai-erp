@props([
    'number',
    'title',
    'active' => false,
])

<div class="card stack-sm {{ $active ? 'is-selected' : 'opacity-60' }}">
    <div class="row-sm items-center">
        <span class="step-badge {{ $active ? 'is-active' : '' }}">{{ $number }}</span>
        <h3 class="h-sub">{{ $title }}</h3>
    </div>
    <div class="stack-sm">{{ $slot }}</div>
</div>
