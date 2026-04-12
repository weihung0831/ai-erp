{{-- 共用登出 Modal + Splash --}}
{{-- 需要父層 x-data 提供：showLogoutModal, loggingOut, confirmLogout() --}}
<x-ui.modal title="確認登出" show="showLogoutModal" maxWidth="sm">
    <p class="text-[var(--text-secondary)]">確定要登出 AI ERP 平台嗎？</p>
    <x-slot:footer>
        <button class="btn btn-secondary btn-sm" @click="showLogoutModal = false">取消</button>
        <button class="btn btn-danger btn-sm" @click="confirmLogout()">登出</button>
    </x-slot:footer>
</x-ui.modal>

<x-ui.splash show="loggingOut" subtitle="正在登出" />
