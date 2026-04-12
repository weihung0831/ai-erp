@props([
    'company',
    'user',
])

<header class="app-header">
    <span class="company">{{ $company }}</span>

    <div class="row-sm items-center">
        <x-ui.theme-toggle variant="labeled" />
        @isset($actions)
            {{ $actions }}
        @endisset
        <x-ui.dropdown align="right" width="180px">
            <x-slot:trigger>
                <button type="button" class="btn btn-text">
                    {{ $user }} ▾
                </button>
            </x-slot:trigger>

            <a href="#" class="dropdown-item">個人設定</a>
            <a href="#" class="dropdown-item">切換租戶</a>
            <div class="dropdown-divider"></div>
            <a href="#" class="dropdown-item">登出</a>
        </x-ui.dropdown>
    </div>
</header>
