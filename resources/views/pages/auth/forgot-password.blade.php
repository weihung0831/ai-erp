<x-layouts.app title="忘記密碼">
    <div class="login-page" x-data="{
        email: '',
        error: null,
        success: false,
        loading: false,
        async submit() {
            this.error = null;
            this.loading = true;
            try {
                await window.axios.post('/api/forgot-password', {
                    email: this.email,
                });
                this.success = true;
            } catch (e) {
                this.error = e.response?.data?.message || '發送失敗，請稍後再試';
            } finally {
                this.loading = false;
            }
        }
    }">
        <div class="login-card card">
            <div class="login-header stack-sm">
                <h1 class="h-card">忘記密碼</h1>
                <p class="login-subtitle">輸入您的 Email，我們會寄送重設密碼連結</p>
            </div>

            <template x-if="error">
                <x-ui.alert type="error" :dismissible="false">
                    <span x-text="error"></span>
                </x-ui.alert>
            </template>

            <template x-if="success">
                <div class="stack-md">
                    <x-ui.alert type="success" :dismissible="false">
                        重設連結已寄出，請檢查您的 Email
                    </x-ui.alert>
                    <div class="login-footer">
                        <a href="{{ route('login') }}" class="login-link">返回登入</a>
                    </div>
                </div>
            </template>

            <template x-if="!success">
                <div class="stack-md">
                    <form class="stack-md" @submit.prevent="submit">
                        <x-form.input
                            name="email"
                            label="Email"
                            type="email"
                            :required="true"
                            placeholder="name@company.com"
                            x-model="email"
                        />

                        <x-ui.button
                            variant="primary"
                            type="submit"
                            class="login-submit"
                            ::disabled="loading || !email"
                        >
                            <span x-show="!loading">寄送重設連結</span>
                            <span x-show="loading" x-cloak>寄送中…</span>
                        </x-ui.button>
                    </form>

                    <div class="login-footer">
                        <a href="{{ route('login') }}" class="login-link">返回登入</a>
                    </div>
                </div>
            </template>
        </div>
    </div>
</x-layouts.app>
