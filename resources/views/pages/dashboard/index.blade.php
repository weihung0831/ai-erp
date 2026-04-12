<x-layouts.app title="Dashboard">
<div class="flex h-screen"
     x-data="{
        sidebarOpen: localStorage.getItem('sidebarOpen') !== 'false',
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
        get subtitleText() {
            const y = new Date().getFullYear();
            if (this.period === '本月') return new Date().toLocaleDateString('zh-TW', { year:'numeric', month:'long' }) + '業務概覽';
            if (this.period === '本季') return y + ' Q' + Math.ceil((new Date().getMonth()+1)/3) + ' 業務概覽';
            return y + ' 年度業務概覽';
        },
        trendText(t) {
            if (t === null || t === undefined) return null;
            if (t === 0) return '➡ 持平';
            const pct = Math.abs(t * 100).toFixed(1);
            return (t > 0 ? '▲ ' : '▼ ') + pct + '%';
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
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto relative"
         x-data="{ showScrollTop: false }"
         @scroll="showScrollTop = $el.scrollTop > 300">
        <div class="dash-content">
            {{-- Header --}}
            <div class="dashboard-header">
                <h1>Dashboard</h1>
                <div class="dashboard-header-row">
                    <p class="dashboard-subtitle" x-text="subtitleText"></p>
                    <div class="dash-period-group">
                        <template x-for="opt in periodOptions" :key="opt">
                            <button class="dash-period-btn"
                                    :class="{ 'is-active': period === opt }"
                                    @click="period = opt"
                                    x-text="opt"></button>
                        </template>
                    </div>
                </div>
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
                                             :class="stat.severity === 'warning' ? 'is-warning' : stat.value_format === 'currency' ? 'is-currency' : 'is-count'">
                                            <span class="stat-label" x-text="stat.label"></span>
                                            <span class="stat-value" x-text="stat.formatted_value"></span>
                                            <template x-if="stat.trend !== null && stat.trend !== undefined">
                                                <span class="stat-trend"
                                                      :class="stat.trend > 0 ? 'stat-trend-up' : stat.trend < 0 ? 'stat-trend-down' : 'stat-trend-neutral'"
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

        <button class="scroll-top-btn"
                x-show="showScrollTop"
                x-transition.opacity
                @click="$el.closest('.overflow-y-auto').scrollTo({ top: 0, behavior: 'smooth' })">
            ↑ 回到頂部
        </button>
    </div>

    @include('partials.logout')
</div>
</x-layouts.app>
