<x-layouts.app title="重設密碼">
    <div class="login-page" x-data="{
        email: @js($email),
        token: @js($token),
        password: '',
        password_confirmation: '',
        error: null,
        success: false,
        loading: false,
        async submit() {
            this.error = null;
            this.loading = true;
            try {
                await window.axios.post('/api/reset-password', {
                    email: this.email,
                    token: this.token,
                    password: this.password,
                    password_confirmation: this.password_confirmation,
                });
                this.success = true;
            } catch (e) {
                this.error = e.response?.data?.message || '重設失敗，請稍後再試';
            } finally {
                this.loading = false;
            }
        }
    }">
        <div class="login-card card">
            <div class="login-header stack-sm">
                <h1 class="h-card">重設密碼</h1>
                <p class="login-subtitle">請輸入新密碼</p>
            </div>

            <template x-if="error">
                <x-ui.alert type="error" :dismissible="false">
                    <span x-text="error"></span>
                </x-ui.alert>
            </template>

            <template x-if="success">
                <div class="stack-md">
                    <x-ui.alert type="success" :dismissible="false">
                        密碼已重設成功
                    </x-ui.alert>
                    <a href="{{ route('login') }}">
                        <x-ui.button variant="primary" class="login-submit">
                            前往登入
                        </x-ui.button>
                    </a>
                </div>
            </template>

            <template x-if="!success">
                <form class="stack-md" @submit.prevent="submit">
                    <x-form.input
                        name="password"
                        label="新密碼"
                        type="password"
                        :required="true"
                        placeholder="至少 8 字元"
                        x-model="password"
                    />

                    <x-form.input
                        name="password_confirmation"
                        label="確認新密碼"
                        type="password"
                        :required="true"
                        placeholder="再次輸入新密碼"
                        x-model="password_confirmation"
                    />

                    <x-ui.button
                        variant="primary"
                        type="submit"
                        class="login-submit"
                        ::disabled="loading || !password || !password_confirmation"
                    >
                        <span x-show="!loading">重設密碼</span>
                        <span x-show="loading" x-cloak>重設中…</span>
                    </x-ui.button>
                </form>
            </template>
        </div>
    </div>
</x-layouts.app>
