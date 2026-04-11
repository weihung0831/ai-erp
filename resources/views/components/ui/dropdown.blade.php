@props([
    'align' => 'right',
    'width' => '200px',
])

@php
    $alignClass = $align === 'left' ? 'left-0' : 'right-0';
@endphp

<div x-data="{ open: false }" @click.outside="open = false" class="relative inline-block">
    <div @click="open = !open" class="cursor-pointer">
        {{ $trigger }}
    </div>

    <div
        x-show="open"
        x-transition.origin.top
        x-cloak
        class="dropdown mt-2 {{ $alignClass }}"
        style="width: {{ $width }};"
    >
        {{ $slot }}
    </div>
</div>
