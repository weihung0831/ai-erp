@props([
    'title',
    'show',
    'maxWidth' => 'md',
])

@php
    $widthClass = match ($maxWidth) {
        'sm' => 'modal-sm',
        'lg' => 'modal-lg',
        default => 'modal-md',
    };
@endphp

<template x-teleport="body">
    <div
        x-show="{{ $show }}"
        x-transition.opacity
        x-cloak
        class="modal-overlay"
        @click.self="{{ $show }} = false"
        @keydown.escape.window="{{ $show }} = false"
    >
        <div {{ $attributes->merge(['class' => "modal {$widthClass}"]) }}>
            <div class="flex items-start justify-between">
                <h2 class="modal-title">{{ $title }}</h2>
                <button type="button" @click="{{ $show }} = false" class="btn btn-text" aria-label="關閉">&times;</button>
            </div>
            <div class="stack-md">
                {{ $slot }}
            </div>
            @isset($footer)
                <div class="row-sm mt-6 justify-end">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</template>
