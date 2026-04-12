@props([
    'show',
    'subtitle' => '載入中',
])

<template x-teleport="body">
    <div class="login-splash" x-show="{{ $show }}" x-cloak
         x-transition:enter="splash-enter"
         x-transition:enter-start="splash-enter-start"
         x-transition:enter-end="splash-enter-end"
    >
        <div class="splash-particles">
            <span class="splash-particle"></span>
            <span class="splash-particle"></span>
            <span class="splash-particle"></span>
            <span class="splash-particle"></span>
            <span class="splash-particle"></span>
            <span class="splash-particle"></span>
            <span class="splash-particle"></span>
            <span class="splash-particle"></span>
        </div>
        <div class="splash-scanline"></div>
        <div class="splash-pulse"></div>
        <div class="splash-pulse delay-1"></div>
        <div class="splash-pulse delay-2"></div>
        <div class="splash-ring">
            <div class="splash-ring-arc"></div>
            <div class="splash-ring-arc delay-1"></div>
            <div class="splash-ring-arc delay-2"></div>
            <div class="splash-ring-glow"></div>
        </div>
        <div class="splash-text">
            <span class="splash-brand">AI ERP</span>
            <span class="splash-sub">{{ $subtitle }}</span>
            <div class="splash-progress">
                <div class="splash-progress-bar"></div>
            </div>
        </div>
    </div>
</template>
