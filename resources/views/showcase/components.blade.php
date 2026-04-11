<x-layouts.app title="元件庫展示 — AI ERP">
    <div x-data="{ confirmOpen: false, modalOpen: false, toastTick: 0 }" class="flex min-h-screen">

        {{-- 元件目錄使用自訂錨點導覽，與 <x-layout.sidebar> 的 items prop 不同用途。 --}}
        <aside class="sidebar showcase-sidebar-fixed" role="navigation" aria-label="元件目錄">
            <div class="sidebar-logo">元件庫 (42)</div>
            <nav class="stack-xs">
                @foreach ($sections as $section)
                    <a href="#{{ $section['id'] }}" class="sidebar-item">
                        <span aria-hidden="true">◆</span>
                        <span>{{ $section['title'] }}</span>
                    </a>
                @endforeach
            </nav>
            <div class="showcase-sidebar-footer">
                <button
                    type="button"
                    @click="theme = (theme === 'dark' ? 'light' : 'dark'); localStorage.setItem('theme', theme); document.documentElement.setAttribute('data-theme', theme)"
                    class="sidebar-item showcase-theme-toggle"
                >
                    <span aria-hidden="true" x-text="theme === 'dark' ? '☀' : '☾'"></span>
                    <span x-text="theme === 'dark' ? '切換 Light' : '切換 Dark'"></span>
                </button>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="app-header showcase-header">
                <div class="stack-xs">
                    <span class="company">Blade 元件庫展示</span>
                    <span class="text-[12px] text-[var(--text-tertiary)]">依據 docs/spec/00-component-library.md 實作 · 42 / 42 元件</span>
                </div>
                <div class="row-sm items-center">
                    <x-ui.badge variant="success">Phase 1 ✓</x-ui.badge>
                    <x-ui.badge variant="success">Phase 2 ✓</x-ui.badge>
                    <x-ui.badge variant="success">Phase 3 ✓</x-ui.badge>
                </div>
            </header>

            <main class="stack-xl showcase-main">
                <section class="stack-sm">
                    <h1 class="h-hero">AI ERP 元件庫</h1>
                    <p class="showcase-lede">
                        所有 Blade Component 依據 Claude DESIGN.md 設計系統實作，支援 dark mode（點左下切換），
                        遵循 ring-based shadow、warm neutrals、Noto Serif/Sans TC 字型體系。
                    </p>
                </section>

                {{-- ========== Layout ========== --}}
                <section id="layout" class="stack-lg">
                    <h2 class="h-section">Layout 版面</h2>

                    <div class="stack-md">
                        <h3 class="h-sub">&lt;x-layout.sidebar&gt;</h3>
                        <p class="text-[14px] text-[var(--text-secondary)]">動態側邊導覽列，深色背景搭配 warm silver 文字。（左側已在使用）</p>
                        <div class="showcase-sidebar-demo">
                            <x-layout.sidebar :items="$navItems" active="/chat" brand="Demo 公司 ERP" />
                        </div>
                    </div>

                    <div class="stack-md">
                        <h3 class="h-sub">&lt;x-layout.header&gt;</h3>
                        <p class="text-[14px] text-[var(--text-secondary)]">頂部導覽列，右側含使用者 dropdown。</p>
                        <x-layout.header company="永豐科技股份有限公司" user="王大明" />
                    </div>

                    <div class="stack-md">
                        <h3 class="h-sub">&lt;x-layout.page&gt;</h3>
                        <p class="text-[14px] text-[var(--text-secondary)]">頁面框架，包 sidebar + header + main。本展示頁本身就是個 page layout。</p>
                    </div>
                </section>

                {{-- ========== Chat ========== --}}
                <section id="chat" class="stack-lg">
                    <h2 class="h-section">Chat 聊天</h2>

                    <div class="card stack-md">
                        <h3 class="h-sub">&lt;x-chat.bubble&gt; · &lt;x-chat.typing&gt;</h3>
                        <div class="stack-md">
                            <x-chat.bubble type="user">這個月的營收是多少？</x-chat.bubble>
                            <x-chat.bubble type="ai" :confidence="0.97">
                                本月總營收為 <strong>NT$1,234,567</strong>，較上月成長 12.3%。
                            </x-chat.bubble>
                            <x-chat.bubble type="ai" :confidence="0.82" sql="SELECT SUM(total_amount) FROM orders WHERE MONTH(created_at) = MONTH(NOW());">
                                建議確認：您是指 <em>本月已出貨</em> 還是 <em>本月下單</em> 的訂單？
                            </x-chat.bubble>
                            <x-chat.bubble type="system">—— 2 分鐘前 ——</x-chat.bubble>
                            <x-chat.typing />
                        </div>
                    </div>

                    <div class="card stack-md">
                        <h3 class="h-sub">&lt;x-chat.input&gt;</h3>
                        <x-chat.input placeholder="請輸入您的問題..." />
                    </div>

                    <div class="card stack-md">
                        <h3 class="h-sub">&lt;x-chat.quick-actions&gt;</h3>
                        <x-chat.quick-actions :actions="['本月營收', '庫存狀況', '應收帳款', 'Top 10 客戶']" />
                    </div>

                    <div class="card stack-md">
                        <h3 class="h-sub">&lt;x-chat.confidence&gt;</h3>
                        <div class="row-sm items-center">
                            <x-chat.confidence :score="0.98" />
                            <x-chat.confidence :score="0.82" />
                            <x-chat.confidence :score="0.45" />
                        </div>
                    </div>

                    <div class="card stack-md">
                        <h3 class="h-sub">&lt;x-chat.result-table&gt;（聊天內嵌）</h3>
                        <x-chat.bubble type="ai" :confidence="0.96">
                            最近 3 筆訂單：
                            <x-chat.result-table :headers="$chatResultHeaders" :rows="$chatResultRows" />
                        </x-chat.bubble>
                    </div>

                    <div class="card stack-md">
                        <h3 class="h-sub">&lt;x-chat.result-number&gt;</h3>
                        <x-chat.bubble type="ai" :confidence="0.99">
                            <x-chat.result-number :value="1234567" label="本月營收" format="currency" :compare="1098765" />
                        </x-chat.bubble>
                    </div>
                </section>

                {{-- ========== Data ========== --}}
                <section id="data" class="stack-lg">
                    <h2 class="h-section">Data 資料</h2>

                    <div class="stack-md">
                        <h3 class="h-sub">&lt;x-data.stat-card&gt;</h3>
                        <div class="grid-auto">
                            <x-data.stat-card label="本月營收" value="NT$1,234,567" :trend="0.123" />
                            <x-data.stat-card label="待出貨訂單" value="42" :trend="-0.08" />
                            <x-data.stat-card label="活躍客戶" value="128" :trend="0.05" />
                            <x-data.stat-card label="庫存週轉率" value="3.2x" />
                        </div>
                    </div>

                    <div class="stack-md">
                        <h3 class="h-sub">&lt;x-data.table&gt;</h3>
                        <x-data.table :headers="$tableHeaders" :rows="$tableRows" />
                    </div>

                    <div class="stack-md">
                        <h3 class="h-sub">&lt;x-data.pagination&gt;</h3>
                        <x-data.pagination :total="237" :perPage="15" :currentPage="4" />
                    </div>

                    <div class="stack-md">
                        <h3 class="h-sub">&lt;x-data.empty-state&gt;</h3>
                        <x-data.empty-state message="目前沒有符合條件的資料" action="新增客戶" actionUrl="#" />
                    </div>
                </section>

                {{-- ========== Form ========== --}}
                <section id="form" class="stack-lg">
                    <h2 class="h-section">Form 表單</h2>

                    <div class="card stack-md showcase-form-card">
                        <x-form.input name="company" label="公司名稱" required placeholder="例：永豐科技" />
                        <x-form.input name="email" label="聯絡 Email" type="email" required value="invalid-email" error="Email 格式不正確" />
                        <x-form.textarea name="note" label="備註" rows="3" placeholder="輸入備註..." />
                        <x-form.select
                            name="industry"
                            label="產業別"
                            :options="[
                                ['value' => 'restaurant', 'label' => '餐飲業'],
                                ['value' => 'retail', 'label' => '零售業'],
                                ['value' => 'manufacturing', 'label' => '製造業'],
                            ]"
                            selected="retail"
                        />
                        <x-form.date-picker name="start" label="開始日期" value="2026-04-01" />
                        <x-form.toggle name="notify" label="接收每日報表" :checked="true" />
                        <x-form.checkbox name="agree" label="我同意服務條款" :checked="true" />
                    </div>
                </section>

                {{-- ========== UI ========== --}}
                <section id="ui" class="stack-lg">
                    <h2 class="h-section">UI 基礎</h2>

                    <div class="card stack-md">
                        <h3 class="h-sub">&lt;x-ui.button&gt;</h3>
                        <div class="row-md items-center">
                            <x-ui.button variant="primary">送出</x-ui.button>
                            <x-ui.button variant="secondary">取消</x-ui.button>
                            <x-ui.button variant="text">了解更多</x-ui.button>
                            <x-ui.button variant="danger">刪除</x-ui.button>
                            <x-ui.button variant="primary" size="sm">小按鈕</x-ui.button>
                            <x-ui.button variant="primary" size="lg">大按鈕</x-ui.button>
                            <x-ui.button variant="primary" disabled>停用</x-ui.button>
                            <x-ui.button variant="primary" loading>載入中</x-ui.button>
                        </div>
                    </div>

                    <div class="card stack-md">
                        <h3 class="h-sub">&lt;x-ui.badge&gt;</h3>
                        <div class="row-sm items-center">
                            <x-ui.badge>預設</x-ui.badge>
                            <x-ui.badge variant="success">已完成</x-ui.badge>
                            <x-ui.badge variant="warning">處理中</x-ui.badge>
                            <x-ui.badge variant="danger">逾期</x-ui.badge>
                        </div>
                    </div>

                    <div class="card stack-md">
                        <h3 class="h-sub">&lt;x-ui.alert&gt;</h3>
                        <x-ui.alert type="info">查詢結果可能包含未出貨訂單。</x-ui.alert>
                        <x-ui.alert type="success">Schema 已成功建立。</x-ui.alert>
                        <x-ui.alert type="warning">您的查詢配額剩餘 20%。</x-ui.alert>
                        <x-ui.alert type="error">連線超時，請稍後再試。</x-ui.alert>
                    </div>

                    <div class="card stack-md">
                        <h3 class="h-sub">&lt;x-ui.modal&gt;</h3>
                        <div class="row-sm">
                            <x-ui.button variant="primary" @click="modalOpen = true">開啟 Modal</x-ui.button>
                        </div>
                        <x-ui.modal show="modalOpen" title="確認刪除" maxWidth="sm">
                            這筆資料刪除後無法復原，確定要繼續嗎？

                            <x-slot:footer>
                                <x-ui.button variant="secondary" @click="modalOpen = false">取消</x-ui.button>
                                <x-ui.button variant="danger" @click="modalOpen = false">確認刪除</x-ui.button>
                            </x-slot:footer>
                        </x-ui.modal>
                    </div>

                    <div class="card stack-md">
                        <h3 class="h-sub">&lt;x-ui.dropdown&gt;</h3>
                        <x-ui.dropdown align="left">
                            <x-slot:trigger>
                                <x-ui.button variant="secondary">選單 ▾</x-ui.button>
                            </x-slot:trigger>
                            <a href="#" class="dropdown-item">個人設定</a>
                            <a href="#" class="dropdown-item">團隊管理</a>
                            <div class="dropdown-divider"></div>
                            <a href="#" class="dropdown-item">登出</a>
                        </x-ui.dropdown>
                    </div>

                    <div class="card stack-md">
                        <h3 class="h-sub">&lt;x-ui.loading&gt; (skeleton)</h3>
                        <div class="stack-sm">
                            <x-ui.loading type="line" height="16px" width="60%" />
                            <x-ui.loading type="line" height="16px" width="80%" />
                            <x-ui.loading type="line" height="16px" width="40%" />
                        </div>
                        <div class="row-sm items-center">
                            <x-ui.loading type="circle" height="48px" width="48px" />
                            <x-ui.loading type="block" height="48px" width="200px" />
                        </div>
                    </div>

                    <div class="card stack-md">
                        <h3 class="h-sub">&lt;x-ui.toast&gt;</h3>
                        <p class="text-[14px] text-[var(--text-secondary)]">實際使用時 toast 會 teleport 到 body 右上角，自動 3 秒消失。每次點擊重新計時。</p>
                        <div class="row-sm">
                            <x-ui.button variant="primary" @click="toastTick++">觸發 Toast</x-ui.button>
                        </div>
                        <template x-for="i in toastTick" :key="i">
                            <x-ui.toast type="success" message="已成功儲存變更" :duration="3000" />
                        </template>
                    </div>

                    <div class="card stack-md">
                        <h3 class="h-sub">&lt;x-ui.tooltip&gt;</h3>
                        <div class="row-md items-center showcase-tooltip-demo">
                            <x-ui.tooltip text="頂部提示" position="top">
                                <x-ui.button variant="secondary">Top</x-ui.button>
                            </x-ui.tooltip>
                            <x-ui.tooltip text="底部提示" position="bottom">
                                <x-ui.button variant="secondary">Bottom</x-ui.button>
                            </x-ui.tooltip>
                            <x-ui.tooltip text="左側提示" position="left">
                                <x-ui.button variant="secondary">Left</x-ui.button>
                            </x-ui.tooltip>
                            <x-ui.tooltip text="右側提示" position="right">
                                <x-ui.button variant="secondary">Right</x-ui.button>
                            </x-ui.tooltip>
                        </div>
                    </div>
                </section>

                {{-- ========== Build ========== --}}
                <section id="build" class="stack-lg">
                    <h2 class="h-section">Build 建構 (Phase 2)</h2>

                    <div class="stack-md">
                        <h3 class="h-sub">&lt;x-build.industry-picker&gt;</h3>
                        <x-build.industry-picker :industries="$industries" selected="retail" />
                    </div>

                    <div class="stack-md">
                        <h3 class="h-sub">&lt;x-build.module-checklist&gt;</h3>
                        <x-build.module-checklist :modules="$moduleList" :selected="['inventory', 'sales']" />
                    </div>

                    <div class="stack-md">
                        <h3 class="h-sub">&lt;x-build.module-card&gt; · &lt;x-build.column-row&gt;</h3>
                        <div class="grid-auto">
                            <x-build.module-card
                                :name="$schemaTables[0]['name']"
                                :display-name="$schemaTables[0]['displayName']"
                                :columns="$schemaTables[0]['columns']"
                                :relations="$schemaTables[0]['relations']"
                            />
                        </div>
                    </div>

                    <div class="stack-md">
                        <h3 class="h-sub">&lt;x-build.schema-preview&gt;</h3>
                        <x-build.schema-preview :tables="$schemaTables" />
                    </div>

                    <div class="stack-md">
                        <h3 class="h-sub">&lt;x-build.confirm-dialog&gt;</h3>
                        <div class="row-sm">
                            <x-ui.button variant="primary" @click="confirmOpen = true">開啟建構確認</x-ui.button>
                        </div>
                        <x-build.confirm-dialog show="confirmOpen" :tables="$schemaTables" />
                    </div>
                </section>

                {{-- ========== CRUD ========== --}}
                <section id="crud" class="stack-lg">
                    <h2 class="h-section">CRUD 動態 (Phase 2)</h2>

                    <div class="stack-md">
                        <h3 class="h-sub">&lt;x-crud.dynamic-table&gt;</h3>
                        <x-crud.dynamic-table
                            module="員工"
                            :schema="[
                                ['name' => 'name', 'display_name' => '姓名', 'type' => 'string'],
                                ['name' => 'age', 'display_name' => '年齡', 'type' => 'integer'],
                                ['name' => 'joined_at', 'display_name' => '入職日期', 'type' => 'date'],
                            ]"
                            :rows="$dynamicRows"
                        />
                    </div>

                    <div class="stack-md">
                        <h3 class="h-sub">&lt;x-crud.dynamic-form&gt;</h3>
                        <x-crud.dynamic-form
                            module="員工"
                            :schema="$dynamicSchema"
                            :values="['name' => '王大明', 'age' => 28, 'status' => 'active', 'remote' => true]"
                            :is-edit="true"
                        />
                    </div>
                </section>

                {{-- ========== Onboarding ========== --}}
                <section id="onboarding" class="stack-lg">
                    <h2 class="h-section">Onboarding (Phase 3)</h2>

                    <div class="stack-md">
                        <h3 class="h-sub">&lt;x-onboarding.progress&gt;</h3>
                        <x-onboarding.progress :current="2" :total="4" />
                    </div>

                    <div class="stack-md">
                        <h3 class="h-sub">&lt;x-onboarding.step&gt;</h3>
                        <div class="grid-auto">
                            <x-onboarding.step :number="1" title="選擇產業" :active="false">
                                <p class="text-[14px] text-[var(--text-secondary)]">已完成：零售業</p>
                            </x-onboarding.step>
                            <x-onboarding.step :number="2" title="勾選模組" :active="true">
                                <p class="text-[14px] text-[var(--text-secondary)]">請從推薦清單中勾選您需要的模組。</p>
                                <x-ui.button variant="primary" size="sm">下一步</x-ui.button>
                            </x-onboarding.step>
                            <x-onboarding.step :number="3" title="確認 Schema" :active="false">
                                <p class="text-[14px] text-[var(--text-secondary)]">AI 會根據您的選擇產生建議 schema。</p>
                            </x-onboarding.step>
                        </div>
                    </div>
                </section>

                {{-- ========== Billing ========== --}}
                <section id="billing" class="stack-lg">
                    <h2 class="h-section">Billing 訂閱 (Phase 3)</h2>

                    <div class="stack-md">
                        <h3 class="h-sub">&lt;x-billing.plan-card&gt;</h3>
                        <div class="grid-auto">
                            <x-billing.plan-card
                                name="Starter"
                                price="NT$1,280/月"
                                :features="['1 位使用者', '每月 500 次查詢', '基礎模組', 'Email 支援']"
                            />
                            <x-billing.plan-card
                                name="Pro"
                                price="NT$4,800/月"
                                :features="['5 位使用者', '每月 5,000 次查詢', '全部模組', '優先客服', 'Chat-to-build']"
                                :recommended="true"
                            />
                            <x-billing.plan-card
                                name="Enterprise"
                                price="聯繫報價"
                                :features="['無限使用者', '無限查詢', '專屬 LLM 額度', 'SLA 99.9%', 'SSO']"
                                :current="true"
                            />
                        </div>
                    </div>

                    <div class="card stack-md">
                        <h3 class="h-sub">&lt;x-billing.usage-bar&gt;</h3>
                        <x-billing.usage-bar label="本月查詢次數" :used="3250" :limit="5000" />
                        <x-billing.usage-bar label="資料儲存量" :used="824" :limit="1000" />
                        <x-billing.usage-bar label="API 請求" :used="4950" :limit="5000" />
                    </div>
                </section>

                {{-- ========== Admin ========== --}}
                <section id="admin" class="stack-lg">
                    <h2 class="h-section">Admin 管理 (Phase 3)</h2>

                    <div class="stack-md">
                        <h3 class="h-sub">&lt;x-admin.tenant-card&gt;</h3>
                        <div class="grid-auto">
                            @foreach ($tenantDemos as $tenant)
                                <x-admin.tenant-card :tenant="$tenant" />
                            @endforeach
                        </div>
                    </div>

                    <div class="stack-md">
                        <h3 class="h-sub">&lt;x-admin.trend-chart&gt;</h3>
                        <x-admin.trend-chart
                            :labels="['04/05', '04/06', '04/07', '04/08', '04/09', '04/10', '04/11']"
                            :datasets="[
                                ['label' => '查詢量', 'data' => [820, 910, 1050, 1200, 1180, 1320, 1480], 'color' => '#c96442'],
                                ['label' => '新增訂單', 'data' => [420, 460, 510, 580, 620, 680, 720], 'color' => '#2d6a4f'],
                            ]"
                            type="line"
                            :target="1400"
                        />

                        <x-admin.trend-chart
                            :labels="['一月', '二月', '三月', '四月']"
                            :datasets="[
                                ['label' => '月營收 (千)', 'data' => [980, 1120, 1250, 1480], 'color' => '#c96442'],
                            ]"
                            type="bar"
                        />
                    </div>
                </section>

                <footer class="showcase-footer">
                    <p class="text-[12px] text-[var(--text-tertiary)]">
                        AI ERP 平台元件庫 · 依據 <code>docs/spec/00-component-library.md</code> 實作 · {{ date('Y') }}
                    </p>
                </footer>
            </main>
        </div>
    </div>
</x-layouts.app>
