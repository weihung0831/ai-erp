{{-- 共用側邊欄：Dashboard / 聊天頁面導覽 + 使用者選單 + 登出 --}}
{{-- 需要父層 x-data 提供：sidebarOpen, showLogoutModal, loggingOut, confirmLogout() --}}
<aside class="sidebar is-collapsible" :class="{ 'is-collapsed': !sidebarOpen }">
    <div class="sidebar-head">
        <span class="sidebar-logo" x-show="sidebarOpen" x-transition.opacity>AI ERP</span>
        <button class="sidebar-toggle"
                @click="sidebarOpen = !sidebarOpen; localStorage.setItem('sidebarOpen', sidebarOpen)"
                :title="sidebarOpen ? '收合側邊欄' : '展開側邊欄'">
            <span x-text="sidebarOpen ? '«' : '»'"></span>
        </button>
    </div>

    <div class="sidebar-body" x-show="sidebarOpen" x-transition.opacity>
        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" class="sidebar-nav-item {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <a href="{{ route('chat') }}" class="sidebar-nav-item {{ request()->routeIs('chat') ? 'is-active' : '' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                聊天
            </a>
        </nav>

        {{ $slot ?? '' }}

        <div class="sidebar-footer" x-data="{ open: false }" @click.outside="open = false">
            <button type="button" class="sidebar-user-pill" @click="open = !open">
                <span class="sidebar-user-avatar" x-text="($store.auth.user?.name || '使')[0]"></span>
                <span class="sidebar-user-name" x-text="$store.auth.user?.name || '使用者'"></span>
                <svg class="sidebar-user-arrow" :class="{ 'is-open': open }" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M7 10l5 5 5-5z"/></svg>
            </button>
            <div class="sidebar-user-menu" x-show="open" x-transition x-cloak>
                <a href="#" class="dropdown-item" @click.prevent="open = false; showLogoutModal = true">登出</a>
            </div>
        </div>
    </div>
</aside>
