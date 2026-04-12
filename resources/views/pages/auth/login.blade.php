<x-layouts.app title="登入">
    <x-ui.theme-toggle />

    <div class="login-page" x-data="{
        email: '',
        password: '',
        error: null,
        loading: false,
        async login() {
            this.error = null;
            this.loading = true;
            try {
                const res = await window.axios.post('/api/login', {
                    email: this.email,
                    password: this.password,
                });
                $store.auth.setToken(res.data.token);
                window.location.href = '/chat';
            } catch (e) {
                this.error = e.response?.data?.message || '登入失敗，請稍後再試';
            } finally {
                this.loading = false;
            }
        }
    }">
        <div class="login-card card">
            <div class="login-header stack-sm">
                <h1 class="h-card">AI ERP 平台</h1>
                <p class="login-subtitle">登入您的帳號以繼續</p>
            </div>

            <template x-if="error">
                <x-ui.alert type="error" :dismissible="false">
                    <span x-text="error"></span>
                </x-ui.alert>
            </template>

            <form class="stack-md" @submit.prevent="login">
                <x-form.input
                    name="email"
                    label="Email"
                    type="email"
                    :required="true"
                    placeholder="name@company.com"
                    x-model="email"
                />

                <x-form.input
                    name="password"
                    label="密碼"
                    type="password"
                    :required="true"
                    placeholder="輸入密碼"
                    x-model="password"
                />

                <x-ui.button
                    variant="primary"
                    type="submit"
                    class="login-submit"
                    ::disabled="loading || !email || !password"
                >
                    <span x-show="!loading">登入</span>
                    <span x-show="loading" x-cloak>登入中…</span>
                </x-ui.button>
            </form>

            <div class="login-footer">
                <a href="{{ route('password.forgot') }}" class="login-link">忘記密碼？</a>
            </div>
        </div>
    </div>
</x-layouts.app>
