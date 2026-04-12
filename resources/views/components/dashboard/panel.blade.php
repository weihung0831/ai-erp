@props([
    'title' => 'Dashboard',
    'storageKey' => 'dashboard_collapsed',
])

<div
    x-data="{
        collapsed: localStorage.getItem('{{ $storageKey }}') === 'true',
        toggle() {
            this.collapsed = !this.collapsed;
            localStorage.setItem('{{ $storageKey }}', this.collapsed);
        },
    }"
    {{ $attributes->merge(['class' => 'dashboard-panel']) }}
>
    <button class="dashboard-panel-header" @click="toggle()">
        <span class="dashboard-panel-title">{{ $title }}</span>
        <span
            class="dashboard-panel-arrow"
            :class="{ 'is-collapsed': collapsed }"
            aria-hidden="true"
        >&#8963;</span>
    </button>

    <div
        class="dashboard-panel-body"
        x-show="!collapsed"
        x-collapse
        x-cloak
    >
        {{ $slot }}
    </div>
</div>
