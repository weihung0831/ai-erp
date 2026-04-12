<x-layouts.app title="Dashboard">
<div class="flex h-screen"
     x-data="{
        sidebarOpen: localStorage.getItem('sidebarOpen') !== 'false',
        showLogoutModal: false,
        loggingOut: false,
        stats: [],
        loadingStats: true,
        period: '本月',
        periodOptions: ['本月', '本季', '年度'],
        sections: {
            sales:      { title: '銷售概覽' },
            finance:    { title: '財務狀況' },
            operations: { title: '營運數據' },
        },
        get grouped() {
            const g = {};
            const prefixes = ['本月', '本季', '年度'];
            for (const s of this.stats) {
                if (!g[s.section]) g[s.section] = [];
                const hasPeriod = prefixes.some(p => s.label.startsWith(p));
                if (!hasPeriod || s.label.startsWith(this.period)) {
                    g[s.section].push(s);
                }
            }
            return g;
        },
        get vsLabel() {
            return { '本月': 'vs 上月', '本季': 'vs 上季', '年度': 'vs 去年' }[this.period];
        },
        trendText(t) {
            if (t === null || t === undefined) return null;
            if (t === 0) return '➡ 持平';
            const pct = Math.abs(t * 100).toFixed(1);
            return (t > 0 ? '▲ ' : '▼ ') + pct + '%';
        },
        trendClass(t, label) {
            if (t === null || t === undefined || t === 0) return 'stat-trend-neutral';
            const inverted = label.includes('費用') || label.includes('逾期');
            const isGood = inverted ? t < 0 : t > 0;
            return isGood ? 'stat-trend-up' : 'stat-trend-down';
        },
        async confirmLogout() {
            this.showLogoutModal = false;
            this.loggingOut = true;
            const minDelay = new Promise(r => setTimeout(r, 2000));
            try { await Promise.all([window.axios.post('/api/logout'), minDelay]); } catch { await minDelay; }
            $store.auth.clearToken();
            window.location.href = '/login';
        },
     }"
     x-init="
        if (!$store.auth.loggedIn) { window.location.href = '/login'; return; }
        $store.auth.fetchUser();
        try {
            const res = await window.axios.get('/api/dashboard');
            stats = res.data.data || [];
        } catch {}
        loadingStats = false;
     "
>
    @component('partials.sidebar')
    @endcomponent

    {{-- Main area --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <div class="dash-content">
            {{-- Header --}}
            <div class="dashboard-header">
                <div>
                    <h1>Dashboard</h1>
                    <p class="dashboard-subtitle" x-text="new Date().toLocaleDateString('zh-TW', { year:'numeric', month:'long' }) + '業務概覽'"></p>
                </div>
                <select class="dash-period-select" x-model="period">
                    <template x-for="opt in periodOptions" :key="opt">
                        <option :value="opt" x-text="opt"></option>
                    </template>
                </select>
            </div>

            {{-- Loading --}}
            <template x-if="loadingStats">
                <div class="stack-lg">
                    <template x-for="i in 3" :key="i">
                        <div class="stack-sm">
                            <div class="skeleton skeleton-line" style="width: 120px; height: 18px;"></div>
                            <div class="dashboard-grid">
                                <template x-for="j in 3" :key="j">
                                    <div class="dashboard-stat-card">
                                        <div class="skeleton skeleton-line" style="width: 50%; height: 14px;"></div>
                                        <div class="skeleton skeleton-line" style="width: 70%; height: 28px;"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Sections --}}
            <template x-if="!loadingStats && stats.length > 0">
                <div class="stack-xl">
                    <template x-for="(sec, key) in sections" :key="key">
                        <template x-if="grouped[key] && grouped[key].length > 0">
                            <section class="stack-sm">
                                <h2 class="dashboard-section-title" x-text="sec.title"></h2>
                                <div class="dashboard-grid">
                                    <template x-for="stat in grouped[key]" :key="stat.label">
                                        <div class="dashboard-stat-card"
                                             :class="stat.value_format === 'currency' ? 'is-currency' : 'is-count'">
                                            <span class="stat-label" x-text="stat.label"></span>
                                            <span class="stat-value" x-text="stat.formatted_value"></span>
                                            <template x-if="stat.trend !== null && stat.trend !== undefined">
                                                <span class="stat-trend"
                                                      :class="trendClass(stat.trend, stat.label)"
                                                      x-text="trendText(stat.trend) + ' ' + vsLabel">
                                                </span>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </section>
                        </template>
                    </template>
                </div>
            </template>

            {{-- Empty state --}}
            <template x-if="!loadingStats && stats.length === 0">
                <div style="text-align: center; padding: 64px 0;">
                    <p style="color: var(--text-tertiary); font-size: 16px;">尚未設定 Dashboard 指標</p>
                </div>
            </template>
        </div>
    </div>

    @include('partials.logout')
</div>
</x-layouts.app>
