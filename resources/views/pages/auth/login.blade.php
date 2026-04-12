<x-layouts.app title="登入">
    <div class="login-page" x-data="{
        email: '',
        password: '',
        error: null,
        loading: false,
        async login() {
            this.error = null;
            this.loading = true;
            const minDelay = new Promise(r => setTimeout(r, 2000));
            try {
                const [res] = await Promise.all([
                    window.axios.post('/api/login', {
                        email: this.email,
                        password: this.password,
                    }),
                    minDelay,
                ]);
                $store.auth.setToken(res.data.token);
                window.location.href = '/chat';
            } catch (e) {
                await minDelay;
                this.error = e.response?.data?.message || '登入失敗，請稍後再試';
                this.loading = false;
            }
        }
    }">
        <x-ui.splash show="loading" subtitle="系統載入中" />

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
                    登入
                </x-ui.button>
            </form>

            <div class="login-footer">
                <a href="{{ route('password.forgot') }}" class="login-link">忘記密碼？</a>
            </div>
        </div>
    </div>
</x-layouts.app>
