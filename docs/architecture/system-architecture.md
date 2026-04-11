# 系統架構文件：AI ERP 平台

日期：2026-04-11
狀態：已核准
依據：[設計文件](../design/ai-erp-platform.md)

## 系統總覽

系統分為四層：

1. **客戶端** — 瀏覽器，Blade 渲染頁面結構，Alpine.js 管理前端狀態，Axios 呼叫 API 取得資料
2. **Laravel 應用層** — 單一專案內前後端分離。API Controller（`routes/api.php`）回傳 JSON 處理業務邏輯；Web Controller（`routes/web.php`）回傳 Blade view。Service 層包含 AI Service、Tenant Manager、Domain Knowledge
3. **AI Service Layer** — Query Engine（Phase 1）和 Build Engine（Phase 2），透過 LLM Gateway 呼叫 OpenAI GPT-4o
4. **資料層** — 主資料庫（系統運營資料）+ 每個租戶獨立的 MySQL DB（業務資料）

## 模組說明

### 1. 聊天介面（Chat Panel）

產品的核心互動介面。基於 Blade + JavaScript 實作即時聊天體驗。

**職責：**
- 接收使用者自然語言輸入
- 即時顯示 AI 回應（串流輸出）
- 渲染查詢結果（表格、數字、圖表）
- 顯示信心度指標和 SQL 預覽（中信心時）
- 管理對話歷史

**技術細節：**
- Web Controller 回傳 `chat/index.blade.php`，頁面本身不含業務資料
- Alpine.js 元件管理聊天狀態（對話歷史、載入中、信心度顯示）
- Axios 呼叫 `POST /api/chat` 送出訊息，接收 JSON 回應後渲染
- 串流回應透過 Axios + SSE 接收
- UI 遵循 Claude DESIGN.md 設計規範（溫暖色調、parchment 背景）

### 2. Tenant Manager（多租戶管理）

管理 DB-per-tenant 的資料庫連線切換。

**職責：**
- 根據登入用戶識別所屬租戶
- 動態切換 MySQL 連線到對應租戶 DB
- 管理租戶 DB 的建立、備份、刪除
- 確保跨租戶查詢不可能發生

**技術細節：**
- Laravel 的 `DB::connection()` 動態切換
- 主資料庫存放租戶清單、用戶帳號、訂閱資訊
- 租戶 DB 存放該客戶的所有業務資料
- Middleware 層強制每個 request 綁定 tenant context

### 3. AI Service Layer

所有 AI 相關邏輯的統一入口，下分 Query Engine 和 Build Engine。

#### 3a. Query Engine（Phase 1）

將自然語言轉換為 SQL 查詢並回傳結果。

**流程：**
```
使用者輸入 → 意圖分析 → Schema 載入 → SQL 生成 → 驗證 → 執行 → 格式化回應
```

**詳細步驟：**
1. **意圖分析：** LLM 判斷使用者要查什麼（營收？庫存？應收帳款？）
   - 寫入類意圖（INSERT/UPDATE/DELETE）→ 拒絕，提示「目前僅支援查詢」
   - 閒聊類 → 友善回應，引導回查詢
   - 不明確 → 回傳釐清問題
2. **Schema 載入：** 從租戶 DB 讀取 table/column metadata，附上中文註解
3. **SQL 生成：** LLM 透過 function calling 產生 SQL（非 raw text）
4. **驗證層：**
   - 只允許 SELECT 語句
   - 經 EXPLAIN 檢查執行計畫（拒絕掃描行數 > 100,000）
5. **信心度評估：**
   - 高（> 95%）→ 執行 SQL，格式化結果
   - 中（70-95%）→ 執行 SQL，格式化結果 + 附加「建議確認」提示 + SQL 預覽
   - 低（< 70%）→ **不執行 SQL**，回傳釐清問題引導使用者
6. **執行：** 使用 read-only connection 執行 SQL
7. **格式化：** 將結果轉為使用者友善的回應（表格、摘要數字）

**Schema Metadata 格式：**
```json
{
  "table": "orders",
  "display_name": "訂單",
  "columns": [
    {"name": "id", "type": "int", "display_name": "訂單編號"},
    {"name": "total_amount", "type": "decimal", "display_name": "訂單金額"},
    {"name": "created_at", "type": "datetime", "display_name": "建立日期"}
  ],
  "relations": [
    {"target": "customers", "type": "belongs_to", "key": "customer_id"}
  ]
}
```

#### 3b. Build Engine（Phase 2）

根據對話產生 MySQL schema、Laravel Model/Migration、CRUD UI。

**流程：**
```
使用者描述需求 → 產業識別 → Domain Knowledge 匹配 → Schema 生成 → Migration 產生 → UI Scaffold
```

**產出物：**
- MySQL migration 檔案
- Laravel Eloquent Model
- CRUD Controller + Blade 模板
- Blade 模板（遵循 Claude DESIGN.md）

**生成機制：**
1. LLM 根據對話 + domain knowledge 產生結構化 JSON（table 定義、欄位、關聯）
2. JSON 經 Blade template 渲染為 migration/model/controller 原始碼
3. 檔案寫入租戶專屬目錄（`tenants/{id}/generated/`）
4. 預覽模式：先產生預覽頁面供使用者確認，確認後才執行 `artisan migrate` 和路由註冊

### 4. LLM Gateway

統一管理所有 LLM API 呼叫。

**職責：**
- 封裝 OpenAI GPT-4o API 呼叫
- 管理 system prompt（含 schema metadata 和 domain knowledge）
- 信心度評估邏輯
- 回應快取（Redis，需支援 tag；相同查詢 hash 比對，命中時直接回傳，不呼叫 LLM）
- Token 用量追蹤和成本計算
- 錯誤處理和重試機制（重試次數依場景區分，詳見[設計模式 Retry Pattern](../design/design-pattern.md)，間隔 1 秒）

**延遲控制：**
- LLM API timeout：10 秒（超時回傳「系統忙碌，請稍後再試」）
- SQL 執行 timeout：3 秒
- 整體回應目標 < 5 秒（LLM ~3 秒 + SQL ~1 秒 + 格式化 ~0.5 秒）

**介面抽象：**
```php
interface LlmGateway
{
    public function chat(array $messages, array $functions = []): LlmResponse;
    public function estimateConfidence(string $query, string $sql): float;
}
```

預設實作為 `OpenAiGateway`，未來可替換為 Claude 或其他 LLM。

### 5. Domain Knowledge Layer

結構化的產業知識庫，存放在檔案系統中。

**目錄結構：**
```
domain-knowledge/
├── industries/
│   ├── restaurant.json        # 餐飲業
│   ├── manufacturing.json     # 製造業
│   ├── trading.json           # 貿易業
│   └── retail.json            # 零售業
├── common/
│   ├── accounting.json        # 通用會計科目
│   ├── inventory.json         # 通用進銷存
│   └── taiwan-tax.json        # 台灣稅務規則
└── templates/
    ├── invoice.json           # 發票 schema template
    └── purchase-order.json    # 採購單 schema template
```

**單一產業檔案結構：**
```json
{
  "industry": "restaurant",
  "display_name": "餐飲業",
  "common_modules": ["inventory", "sales", "supplier"],
  "tables": [
    {
      "name": "menu_items",
      "display_name": "菜單品項",
      "columns": [...],
      "business_rules": [
        "營業稅 5% 內含",
        "品項可設定為時價"
      ]
    }
  ]
}
```

## 資料庫架構

### 主資料庫（系統層）

存放平台自身的運營資料，所有租戶共用。

```
ai_erp_db
├── tenants            # 租戶清單
│   ├── id
│   ├── name           # 公司名稱
│   ├── db_name        # 該租戶的 DB 名稱
│   ├── industry       # 產業別
│   └── created_at
├── users              # 使用者帳號
│   ├── id
│   ├── tenant_id
│   ├── email
│   ├── password       # bcrypt hash
│   └── role           # admin / user
│   （敏感欄位使用 Laravel encrypted casting）
├── chat_histories     # 對話紀錄
│   ├── id
│   ├── user_id
│   ├── tenant_id
│   ├── message        # 使用者輸入
│   ├── response       # AI 回應
│   ├── sql_generated  # 產生的 SQL（供審計）
│   ├── confidence     # 信心度分數
│   └── created_at
├── query_logs         # 查詢日誌（準確率追蹤）
│   ├── id
│   ├── tenant_id
│   ├── sql
│   ├── result_hash
│   ├── is_correct     # 使用者回饋：正確/錯誤
│   └── tokens_used
└── subscriptions      # 訂閱資訊
    ├── id
    ├── tenant_id
    ├── plan
    └── status
```

### 租戶資料庫（業務層）

每個客戶獨立一個 DB，schema 由 Chat-to-build 或手動建立。

```
ai_erp_tenant_{id}_db
├── schema_metadata    # Schema 中文註解（供 Query Engine 使用）
│   ├── table_name
│   ├── column_name
│   ├── display_name   # 中文欄位名
│   └── description    # 欄位說明
├── [業務資料表]        # 依客戶需求而異
│   ├── customers      # 客戶
│   ├── orders         # 訂單
│   ├── products       # 商品
│   ├── invoices       # 發票
│   └── ...
```

## 認證與授權流程

```
1. 使用者登入 → Laravel Sanctum 發 API token（Bearer token）
2. 每個 request → Auth Middleware 驗證 token
3. Token 解析出 user_id → 查主 DB 取得 tenant_id
4. Tenant Middleware → 切換 DB 連線到 ai_erp_tenant_{id}_db
5. 所有後續操作都在該租戶 DB 上執行
```

注意：Phase 1 使用 Laravel Sanctum（API token），不使用 OAuth2。設計文件中的 OAuth2 + JWT 規劃為未來開放第三方整合時再導入。

**Query Engine 額外安全層：**
- 租戶 DB 連線使用 read-only MySQL user
- SQL 白名單：只允許 SELECT
- EXPLAIN 前置檢查：拒絕全表掃描超過閾值的查詢

## API 設計

### Chat API

```
POST /api/chat
Content-Type: application/json
Authorization: Bearer {token}

{
  "message": "這個月營收多少？",
  "conversation_id": "uuid"  // 可選，用於多輪對話
}

Response:
{
  "reply": "本月營收為 NT$1,234,567",
  "confidence": 0.97,
  "type": "query_result",
  "data": {
    "value": 1234567,
    "currency": "TWD",
    "period": "2026-04"
  },
  "sql_preview": "SELECT SUM(amount) FROM orders WHERE ...",  // 中信心時顯示
  "conversation_id": "uuid"
}
```

### Schema API（Phase 2）

**Step 1：預覽**
```
POST /api/build
Authorization: Bearer {token}

{ "message": "我需要管理客戶和訂單", "conversation_id": "uuid" }

Response:
{
  "type": "build_preview",
  "preview": { "tables": [...], "ui_preview_url": "/preview/build/uuid" },
  "actions": ["confirm", "modify", "cancel"]
}
```

**Step 2：確認執行（僅 admin）**
```
POST /api/build/confirm
Authorization: Bearer {token}

{ "conversation_id": "uuid" }

Response:
{
  "type": "build_complete",
  "created": { "tables": ["customers", "orders"], "routes": ["/customers", "/orders"] },
  "version": "v3"
}
```

## 前後端分離架構

同一個 Laravel 專案內，後端邏輯全部透過 API Controller 回傳 JSON，前端透過 Blade + Alpine.js + Axios 呼叫 API 渲染頁面。

### 路由分層

| 路由檔案 | 職責 | Controller 位置 |
|----------|------|-----------------|
| `routes/api.php` | API 端點，回傳 JSON | `App\Http\Controllers\Api\` |
| `routes/web.php` | 頁面路由，回傳 Blade view | `App\Http\Controllers\Web\` |

- Web Controller 不直接操作資料庫或呼叫 Service，只負責回傳 Blade view
- Blade 頁面透過 Alpine.js + Axios 呼叫 `/api/*` 取得資料
- API Controller 處理所有業務邏輯，呼叫 Service 層

## 目錄結構（Laravel 專案）

```
ai-erp/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/                         # API Controller（回傳 JSON）
│   │   │   │   ├── ChatController.php       # POST /api/chat
│   │   │   │   ├── BuildController.php      # POST /api/build
│   │   │   │   ├── AuthController.php       # POST /api/login, /api/logout
│   │   │   │   └── SchemaController.php     # GET /api/schema（metadata）
│   │   │   └── Web/                         # Web Controller（回傳 Blade view）
│   │   │       ├── ChatPageController.php   # GET /chat
│   │   │       ├── DashboardController.php  # GET /dashboard
│   │   │       └── AdminController.php      # GET /admin/*
│   │   └── Middleware/
│   │       └── TenantMiddleware.php
│   ├── Models/
│   │   ├── Tenant.php
│   │   ├── User.php
│   │   └── ChatHistory.php
│   └── Services/
│       ├── Ai/
│       │   ├── LlmGateway.php              # Interface
│       │   ├── OpenAiGateway.php            # GPT-4o 實作
│       │   ├── QueryEngine.php              # 自然語言 → SQL
│       │   ├── BuildEngine.php              # 自然語言 → Schema + UI
│       │   ├── ConfidenceEstimator.php      # 信心度評估
│       │   └── SqlValidator.php             # SQL 安全驗證
│       ├── Tenant/
│       │   └── TenantManager.php            # 多租戶 DB 切換
│       └── Schema/
│           └── SchemaIntrospector.php       # 讀取租戶 DB metadata
├── database/
│   └── migrations/                          # 主資料庫 migration
├── domain-knowledge/                        # 產業知識庫
│   ├── industries/
│   ├── common/
│   └── templates/
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   └── app.blade.php               # 主版面（含 Alpine.js、Axios CDN）
│   │   ├── chat/
│   │   │   └── index.blade.php             # 聊天頁面（Alpine.js 元件）
│   │   ├── dashboard/
│   │   │   └── index.blade.php             # 儀表板
│   │   ├── admin/
│   │   │   ├── tenants.blade.php           # 租戶管理
│   │   │   └── query-logs.blade.php        # 查詢日誌
│   │   └── components/                      # Blade 共用元件（巢狀命名空間）
│   │       ├── chat/                        # <x-chat.bubble> 等
│   │       ├── data/                        # <x-data.table> 等
│   │       ├── form/                        # <x-form.input> 等
│   │       ├── layout/                      # <x-layout.page> 等
│   │       └── ui/                          # <x-ui.button> 等
│   ├── css/                                 # 遵循 Claude DESIGN.md
│   └── js/                                  # Alpine.js 元件邏輯
├── routes/
│   ├── api.php                              # API 路由（JSON）
│   └── web.php                              # 頁面路由（Blade）
├── docs/
│   ├── design/
│   ├── architecture/
│   └── spec/
├── tests/
│   └── golden/                              # 準確率回歸測試
│       ├── queries.json
│       └── RunGoldenTest.php
├── DESIGN.md                                # Claude 設計規範
└── CLAUDE.md
```
