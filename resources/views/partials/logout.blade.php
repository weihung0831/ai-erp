{{-- 共用登出 Modal + Splash — state 在 $store.auth --}}
<x-ui.modal title="確認登出" show="$store.auth.showLogoutModal" maxWidth="sm">
    <p class="text-[var(--text-secondary)]">確定要登出 AI ERP 平台嗎？</p>
    <x-slot:footer>
        <button class="btn btn-secondary btn-sm" @click="$store.auth.showLogoutModal = false">取消</button>
        <button class="btn btn-danger btn-sm" @click="$store.auth.confirmLogout()">登出</button>
    </x-slot:footer>
</x-ui.modal>

<x-ui.splash show="$store.auth.loggingOut" subtitle="正在登出" />
