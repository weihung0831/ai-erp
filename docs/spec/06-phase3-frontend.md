# 前端規格書：Phase 3 — SaaS 與管理介面

日期：2026-04-11
狀態：已核准
依據：[Phase 3 後端規格](05-phase3-backend.md) / [元件庫](00-component-library.md) / [UI 設計規範](../design/ui-design-spec.md)

## 進度追蹤

### 公開頁面（未登入）
- [ ] 產品首頁
- [ ] 註冊頁
- [ ] Email 驗證結果頁
- [ ] 方案選擇頁

### Onboarding
- [ ] 產業選擇步驟
- [ ] 模組推薦步驟
- [ ] AI 建構步驟
- [ ] 第一筆資料引導
- [ ] Chat-to-query 示範

### 客戶後台
- [ ] 用量儀表板
- [ ] 訂閱管理頁（含付款方式管理）
- [ ] 發票查詢頁

### 平台管理後台
- [ ] 營運儀表板
- [ ] 租戶管理頁
- [ ] Domain Knowledge 管理頁
- [ ] 準確率趨勢頁

### 元件
- [ ] `<x-onboarding.step>` — Onboarding 步驟容器
- [ ] `<x-onboarding.progress>` — 進度指示器
- [ ] `<x-billing.plan-card>` — 方案卡片
- [ ] `<x-billing.usage-bar>` — 用量進度條
- [ ] `<x-admin.tenant-card>` — 租戶資訊卡片
- [ ] `<x-admin.trend-chart>` — 趨勢圖表
- [ ] `<x-data.stat-card>` — 數據統計卡片（儀表板用）

## 頁面清單

### 公開頁面

#### 1. 產品首頁

**路由：** `GET /`
**用途：** 產品介紹、吸引註冊

**頁面結構：**
- Hero 區塊：標題 + 副標題 + CTA 按鈕（免費試用）
- 功能介紹：Chat-to-build / Chat-to-query 雙模組說明
- 方案比較表
- CTA：開始免費試用

#### 2. 註冊頁

**路由：** `GET /register`
**用途：** 新客戶自助註冊

**頁面結構：**
```
├── 註冊卡片
│   ├── <x-form.input> 公司名稱
│   ├── <x-form.input> 聯絡人姓名
│   ├── <x-form.input type="email"> Email
│   ├── <x-form.input type="password"> 密碼
│   ├── <x-form.input type="password"> 確認密碼
│   ├── reCAPTCHA
│   └── <x-ui.button> 註冊
├── 已有帳號？登入
```

**串接 API：**
- `POST /api/register`
- 註冊成功 → 顯示「請至 email 確認」頁面

#### 3. Email 驗證結果頁

**路由：** `GET /verify-email/{token}`
**Web Controller：** `Web\AuthPageController@verifyEmail`
**用途：** 使用者點擊 email 驗證連結後的結果頁

**頁面結構：**
- 成功：顯示「Email 驗證成功！」+ 「開始使用」按鈕（→ `/onboarding`）
- 失敗/過期：顯示「驗證連結無效或已過期」+ 「重新寄送驗證信」按鈕

**串接 API：**
- `GET /api/verify-email/{token}` → 驗證 token，回傳成功或失敗
- `POST /api/verify-email/resend` → 重新寄送驗證信

#### 4. 方案選擇頁

**路由：** `GET /pricing`
**用途：** 展示訂閱方案

**頁面結構：**
```
├── 方案卡片列（水平排列）
│   ├── <x-billing.plan-card> 免費試用
│   ├── <x-billing.plan-card> 基礎版
│   ├── <x-billing.plan-card> 專業版（推薦標籤）
│   └── <x-billing.plan-card> 企業版
└── 功能比較表格
```

### Onboarding

#### 5. Onboarding 流程

**路由：** `GET /onboarding`
**用途：** 新客戶引導，註冊後自動進入

**頁面結構：**
```
<x-layout.page title="歡迎">
    ├── <x-onboarding.progress :step="currentStep" :total="5">
    ├── Step 1：產業選擇
    │   └── <x-build.industry-picker>
    ├── Step 2：模組推薦
    │   └── <x-build.module-checklist>
    ├── Step 3：AI 建構
    │   └── 顯示建構進度（每個模組一個 loading bar）
    ├── Step 4：輸入第一筆資料
    │   └── 動態表單（根據建好的第一個模組）
    └── Step 5：Chat-to-query 示範
        └── 預設問題 + AI 回答示範
</x-layout.page>
```

**Alpine.js 邏輯：**
- `onboardingStore` 管理目前步驟和各步驟資料
- 每步驟完成後呼叫 API 儲存進度
- 任何步驟可跳過（「稍後再說」按鈕）
- 全部完成 → 跳轉到聊天頁

**串接 API：**
- `POST /api/onboarding/industry` → 儲存產業選擇
- `POST /api/build` → 建構選定模組
- `POST /api/build/confirm` → 確認建構
- `POST /api/onboarding/complete` → 標記 onboarding 完成

### 客戶後台

#### 6. 用量儀表板

**路由：** `GET /account/usage`
**用途：** 客戶查看自己的使用狀況

**頁面結構：**
```
<x-layout.page title="用量總覽">
    ├── 統計卡片列
    │   ├── <x-data.stat-card> 本月查詢次數 / 上限
    │   ├── <x-data.stat-card> 本月建構次數 / 上限
    │   └── <x-data.stat-card> 使用者人數 / 上限
    ├── <x-billing.usage-bar> 查詢額度使用進度
    ├── <x-billing.usage-bar> 建構額度使用進度
    └── 升級提示（額度 > 80% 時顯示）
</x-layout.page>
```

**串接 API：**
- `GET /api/account/usage`

#### 7. 訂閱管理頁

**路由：** `GET /account/subscription`
**用途：** 管理訂閱方案

**頁面結構：**
```
<x-layout.page title="訂閱管理">
    ├── 目前方案卡片
    │   ├── 方案名稱、價格、到期日
    │   └── 升級/降級按鈕
    ├── 方案比較（<x-billing.plan-card> 列）
    └── 付款方式
        ├── 目前信用卡（末四碼）
        └── 更換付款方式按鈕 → <x-ui.modal>
</x-layout.page>
```

**串接 API：**
- `GET /api/account/subscription`
- `PUT /api/account/subscription` → 升降級
- `PUT /api/account/payment-method` → 更換付款方式

#### 8. 發票查詢頁

**路由：** `GET /account/invoices`
**用途：** 查詢和下載電子發票

**頁面結構：**
```
<x-layout.page title="電子發票">
    ├── <x-data.table>
    │   ├── 欄位：發票號碼、日期、金額、狀態
    │   └── 操作：下載 PDF
    └── <x-data.pagination>
</x-layout.page>
```

**串接 API：**
- `GET /api/account/invoices?page=1`
- `GET /api/account/invoices/{id}/download`

### 平台管理後台

#### 9. 營運儀表板

**路由：** `GET /admin/dashboard`
**用途：** 平台整體營運數據

**頁面結構：**
```
<x-layout.page title="營運總覽">
    ├── 統計卡片列
    │   ├── <x-data.stat-card> 總租戶數
    │   ├── <x-data.stat-card> 活躍用戶數
    │   ├── <x-data.stat-card> 本月營收
    │   └── <x-data.stat-card> 本月 LLM 成本
    ├── <x-admin.trend-chart> 查詢量趨勢（按日）
    └── <x-admin.trend-chart> 新增租戶趨勢（按週）
</x-layout.page>
```

**串接 API：**
- `GET /api/admin/dashboard`

#### 10. 租戶管理頁

**路由：** `GET /admin/tenants`
**用途：** 管理所有租戶

**頁面結構：**
```
<x-layout.page title="租戶管理">
    ├── 篩選列（狀態、方案、產業）
    ├── <x-data.table>
    │   ├── 欄位：公司名稱、產業、方案、狀態、用量、最後活動
    │   └── 操作：查看詳情、停用/啟用、匯出資料
    └── <x-data.pagination>
</x-layout.page>
```

**串接 API：**
- `GET /api/admin/tenants?page=1&status=active`
- `PATCH /api/admin/tenants/{id}` → 停用/啟用
- `POST /api/admin/tenants/{id}/export` → 匯出資料

#### 11. Domain Knowledge 管理頁

**路由：** `GET /admin/knowledge`
**用途：** 管理產業知識庫和回饋候選

**頁面結構：**
```
<x-layout.page title="知識庫管理">
    ├── Tab 切換
    │   ├── 回饋候選清單
    │   │   ├── <x-data.table> 待審核的 schema pattern
    │   │   └── 操作：審核通過、拒絕
    │   └── 產業知識瀏覽
    │       ├── 產業選擇下拉
    │       └── 該產業的 schema template 清單
</x-layout.page>
```

**串接 API：**
- `GET /api/admin/knowledge/candidates`
- `POST /api/admin/knowledge/candidates/{id}/approve`
- `POST /api/admin/knowledge/candidates/{id}/reject`
- `GET /api/admin/knowledge/industries/{industry}`

#### 12. 準確率趨勢頁

**路由：** `GET /admin/accuracy`
**用途：** 追蹤 AI 準確率趨勢

**頁面結構：**
```
<x-layout.page title="準確率追蹤">
    ├── <x-admin.trend-chart> Query 準確率趨勢（按週）
    │   ├── 關鍵財務線（目標 > 99%）
    │   └── 一般查詢線（目標 > 95%）
    ├── <x-admin.trend-chart> Build 準確率趨勢（按週）
    └── 最近的錯誤查詢列表
        └── <x-data.table>
</x-layout.page>
```

**串接 API：**
- `GET /api/admin/accuracy?period=weekly`
- `GET /api/admin/accuracy/errors?page=1`

## 圖表元件

Phase 3 需要趨勢圖表，建議使用 [Chart.js](https://www.chartjs.org/)（輕量、無依賴、支援 Alpine.js 整合）。

`<x-admin.trend-chart>` 封裝 Chart.js：

| Prop | 型別 | 必填 | 說明 |
|------|------|------|------|
| labels | array | 是 | X 軸標籤（日期） |
| datasets | array | 是 | 資料集 |
| type | string | 否 | `line`（預設）/ `bar` |
| target | float | 否 | 目標線（水平虛線） |
