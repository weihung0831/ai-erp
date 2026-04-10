# 功能規格書：Phase 2 — Chat-to-Build

日期：2026-04-11
狀態：已核准
依據：[設計文件](../design/ai-erp-platform.md) / [架構文件](../architecture/system-architecture.md)

## 進度追蹤

- [ ] US-1：描述需求建立模組
- [ ] US-2：AI 引導式需求釐清
- [ ] US-3：預覽與確認
- [ ] US-4：基於產業模板快速建立
- [ ] US-5：新增欄位和修改 Schema
- [ ] US-6：Schema 版本管理
- [ ] Domain Knowledge 建立（至少 3 個產業）
- [ ] Build Test Suite 建立
- [ ] 程式碼生成 pipeline（Schema JSON → Migration → Model → Controller → Blade）

## 目標

讓客戶透過聊天描述業務需求，AI 自動產生 MySQL schema、Laravel API、CRUD UI，取代團隊的人工開發工作。

## 範圍

### 包含

- 對話式需求收集（AI 引導客戶描述需要管理什麼）
- 產業識別與 domain knowledge 匹配
- MySQL schema 生成（migration 檔案）
- Laravel Model + Controller 生成
- Blade CRUD UI 生成（基於 template scaffold）
- 預覽機制（確認後才執行）
- Schema 版本管理（可回退）

### 不包含

- Free-form UI 設計（僅 template-based scaffold）
- 複雜商業邏輯生成（審批流程、工作流引擎）
- 第三方系統串接（API 對接、金流）
- 報表設計器（複雜圖表、PDF 匯出）

## 使用者故事

### US-1：描述需求建立模組

> 身為客戶，我想跟 AI 說「我需要管理客戶和訂單」，AI 幫我建好資料表和操作介面。

**驗收條件：**
- AI 根據對話產生 table schema（含欄位名稱、型別、關聯）
- 產生對應的 CRUD 介面（列表、新增、編輯、刪除）
- 介面遵循 Claude DESIGN.md 設計規範
- 全程不需要寫程式碼

### US-2：AI 引導式需求釐清

> 身為客戶，當我說「我要管進銷存」，AI 會問我更細的問題（「你需要追蹤批號嗎？」「有多個倉庫嗎？」），幫我把需求定義清楚。

**驗收條件：**
- AI 識別產業別後，根據 domain knowledge 提出關鍵問題
- 每次最多問 3 個問題，不讓客戶覺得太繁瑣
- 問題有預設選項（按鈕），客戶也可以自己打字
- AI 根據回答調整 schema 設計

### US-3：預覽與確認

> 身為客戶，在 AI 建好系統之前，我想先看看它會建什麼，確認沒問題再執行。

**驗收條件：**
- AI 產生 schema 後，以人類可讀的方式展示（表格名稱、欄位、關聯）
- 提供 UI 預覽頁面（模擬的 CRUD 介面）
- 客戶可以說「把電話欄位改成必填」、「加一個備註欄位」進行調整
- 客戶確認後才執行 migration 和部署 UI
- 只有 admin 角色可以確認執行和回退操作

### US-4：基於產業模板快速建立

> 身為餐飲業客戶，我希望 AI 已經知道餐飲業需要什麼（菜單、訂單、食材庫存），不用我從零說起。

**驗收條件：**
- AI 識別產業別後，主動提供該產業的常見模組建議
- 客戶可以勾選需要的模組，跳過不需要的
- 產業模板包含台灣在地商業邏輯的 schema 層級預設值（如 `tax_rate` 欄位預設 0.05）。實際稅務計算邏輯延至 Phase 3

### US-5：新增欄位和修改 Schema

> 身為客戶，系統建好後我發現少了一個欄位，我想用聊天說「客戶資料加一個 LINE ID 欄位」就搞定。

**驗收條件：**
- AI 理解修改意圖，產生對應的 ALTER TABLE migration
- 修改前顯示預覽（「將在客戶表新增 LINE ID 欄位（文字，非必填）」）
- 確認後執行 migration，UI 自動更新
- 修改紀錄可查看，支援回退上一版

### US-6：Schema 版本管理

> 身為客戶，如果 AI 改壞了什麼，我可以回到上一個版本。

**驗收條件：**
- 每次 schema 變更都記錄版本（含時間、變更內容、觸發的對話）
- 管理員頁面列出所有版本歷史
- 可一鍵回退到指定版本（執行反向 migration）

## Build Engine 規格

### 輸入

```php
[
    'message' => '我需要管理客戶和訂單',
    'conversation' => [...],
    'tenant_id' => 'uuid',
    'current_schema' => [...],           // 該租戶現有的 schema（可能為空）
    'industry' => 'restaurant',          // 已識別的產業別（可能為 null）
]
```

### 處理流程

```
1. 產業識別
   ├── 如果尚未識別 → LLM 根據對話判斷產業別
   ├── 載入對應的 domain knowledge
   └── 如果無法判斷 → 詢問客戶

2. 需求分析
   ├── LLM 解析客戶需求，比對 domain knowledge 中的模組
   ├── 識別需要的 tables、columns、relations
   └── 產生釐清問題（如有必要）

3. Schema 設計
   ├── LLM 透過 function calling 回傳結構化 schema JSON
   ├── 包含：table 名稱、欄位定義、型別、約束、關聯、中文註解
   ├── 自動加入通用欄位（id、created_at、updated_at、deleted_at）
   └── 自動套用命名規則（snake_case、複數表名）

4. 程式碼生成
   ├── Schema JSON → Laravel migration 檔案（Blade template 渲染）
   ├── Schema JSON → Eloquent Model（含 fillable、casts、relations）
   ├── Schema JSON → CRUD Controller（index、create、store、edit、update、destroy）
   ├── Schema JSON → Blade 模板（列表頁、表單頁，遵循 DESIGN.md）
   └── Schema JSON → schema_metadata 記錄（供 Query Engine 使用）

5. 預覽
   ├── 產生人類可讀的 schema 摘要
   ├── 產生 UI 預覽頁面
   └── 等待客戶確認

6. 執行（客戶確認後）
   ├── 執行 migration（php artisan migrate）
   ├── 註冊路由
   ├── 部署 UI 頁面
   └── 更新 schema_metadata（供 Chat-to-query 使用）
```

### 輸出

```php
// 預覽階段
[
    'reply' => '我幫你設計了「客戶」和「訂單」兩個模組...',
    'type' => 'build_preview',
    'preview' => [
        'tables' => [
            [
                'name' => 'customers',
                'display_name' => '客戶',
                'columns' => [
                    ['name' => 'name', 'display_name' => '客戶名稱', 'type' => 'string', 'required' => true],
                    ['name' => 'phone', 'display_name' => '電話', 'type' => 'string', 'required' => false],
                    ['name' => 'email', 'display_name' => '信箱', 'type' => 'string', 'required' => false],
                ],
                'relations' => ['has_many: orders']
            ],
            // ...
        ],
        'ui_preview_url' => '/preview/build/uuid',
    ],
    'actions' => ['confirm', 'modify', 'cancel'],
]

// 確認後執行
[
    'reply' => '已建立完成！你可以在左側選單找到「客戶」和「訂單」模組。',
    'type' => 'build_complete',
    'created' => [
        'tables' => ['customers', 'orders'],
        'routes' => ['/customers', '/orders'],
    ],
    'version' => 'v3',
]
```

## 程式碼生成規格

### Migration 模板

AI 產生的 Schema JSON 經 Blade template 渲染為 Laravel migration：

```php
// 輸入 JSON
{
    "table": "customers",
    "columns": [
        {"name": "name", "type": "string", "length": 255, "nullable": false},
        {"name": "phone", "type": "string", "length": 50, "nullable": true},
        {"name": "email", "type": "string", "length": 255, "nullable": true}
    ]
}

// 產出 migration
Schema::create('customers', function (Blueprint $table) {
    $table->id();
    $table->string('name', 255);
    $table->string('phone', 50)->nullable();
    $table->string('email', 255)->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

### 支援的欄位型別

| 型別 | MySQL 對應 | 使用場景 |
|------|-----------|----------|
| string | VARCHAR | 名稱、編號、短文字 |
| text | TEXT | 備註、說明、長文字 |
| integer | INT | 數量、序號 |
| decimal | DECIMAL(12,2) | 金額、價格 |
| boolean | TINYINT(1) | 是否啟用、狀態 |
| date | DATE | 日期（出生日、到期日） |
| datetime | DATETIME | 時間戳記（下單時間） |
| enum | ENUM | 固定選項（狀態、類別） |

### CRUD UI 模板

每個 table 生成四個頁面，遵循 Claude DESIGN.md：

| 頁面 | 路由 | 功能 |
|------|------|------|
| 列表 | GET /customers | 表格顯示、搜尋、分頁、排序 |
| 新增 | GET /customers/create | 表單填寫 |
| 編輯 | GET /customers/{id}/edit | 表單修改 |
| 刪除 | DELETE /customers/{id} | 軟刪除（soft delete） |

### UI 元件對應規則

| 欄位型別 | 表單元件 | 列表元件 |
|----------|----------|----------|
| string | text input | 文字 |
| text | textarea | 截斷文字（hover 顯示全文） |
| integer | number input | 數字 |
| decimal | number input（step=0.01） | NT$ 格式化 |
| boolean | toggle switch | 圖標（勾/叉） |
| date | date picker | 格式化日期 |
| datetime | datetime picker | 格式化日期時間 |
| enum | select dropdown | badge |

## Domain Knowledge 使用規格

### 產業識別流程

```
1. 客戶首次使用 → AI 詢問「你的公司是做什麼的？」
2. LLM 比對 domain-knowledge/industries/ 中的產業清單
3. 匹配成功 → 載入該產業的模組建議
4. 匹配失敗 → 使用 common/ 下的通用模組，不套用產業特定規則
```

### 模組建議格式

```
AI：根據餐飲業的常見需求，建議你建立以下模組：

☑ 菜單管理（品項、價格、分類）
☑ 訂單管理（點餐、結帳、折扣）
☑ 食材庫存（進貨、耗用、盤點）
☐ 供應商管理（廠商、進貨單）
☐ 員工排班（班表、出勤）

勾選你需要的，或告訴我其他需求。
```

## Schema 版本管理

### 版本紀錄結構

```
schema_versions
存放位置：主資料庫
說明：紀錄每次 schema 變更的歷史。回退時，TenantManager 切換到對應的租戶 DB 執行 rollback_sql
├── id
├── tenant_id
├── version          # v1, v2, v3...
├── change_type      # create_table / alter_table / drop_table
├── change_detail    # JSON：變更的具體內容
├── migration_file   # 對應的 migration 檔名
├── rollback_sql     # 反向 migration SQL
├── triggered_by     # 觸發的對話訊息
└── created_at
```

### 回退流程

```
1. 管理員選擇要回退到的版本
2. 系統計算需要回退的 migration 數量
3. 顯示預覽：「將刪除 LINE ID 欄位、移除備註欄位」
4. 確認後依序執行 rollback migration
5. 更新 schema_metadata
```

## 非功能需求

| 項目 | 要求 |
|------|------|
| Schema 生成延遲 | 單模組 < 15 秒；多模組請求顯示中間進度，每個模組獨立回報 |
| Migration 執行 | < 5 秒（單次 migration） |
| 最大 tables 數 | 每租戶 100 tables |
| 最大 columns 數 | 每 table 50 columns |
| 版本歷史保留 | Phase 2：永久。Phase 3 後依訂閱方案調整為 7/30/90 天/永久 |
| 並行限制 | 每個租戶同一時間只允許一個 build 操作（lock 機制） |

## 錯誤處理

| 場景 | 行為 |
|------|------|
| LLM API 逾時（schema 生成中） | 顯示「AI 正在思考，請稍候」，重試一次，仍失敗則提示「請稍後再試」 |
| Migration 執行失敗 | 自動 rollback 該次 migration，顯示「建立失敗，已自動還原」，記錄錯誤日誌 |
| Migration 部分成功（多 table 中途失敗） | 自動 rollback 所有已執行的 migration，回到操作前狀態 |
| Rollback 失敗 | 鎖定該租戶的 build 功能，通知管理員人工介入 |
| 並行 build 衝突 | 第二個請求回傳「目前有其他建立作業進行中，請稍後再試」 |
| Token 額度用盡 | 顯示「本月額度已用完」，記錄 alert |

## UI 預覽機制

預覽頁面不執行 migration，以靜態方式呈現：

- 根據 Schema JSON 渲染 read-only Blade 模板
- 填入模擬範例資料（根據欄位型別自動產生：名稱用「王小明」、金額用「NT$1,234」等）
- 使用者只能看不能操作，頁面標示「預覽模式」浮水印

## 路由註冊策略

動態生成的模組不寫入 `routes/web.php`，改用動態路由載入：

- 從 `schema_metadata` 讀取已建立的 tables
- 在 `RouteServiceProvider` 動態註冊 resource routes
- 左側導覽列根據 `schema_metadata` 動態渲染
- 新模組建立後，導覽列即時更新（無需重新部署）

## 準確率驗證

### Build Test Suite

驗證 AI 產生的 schema 品質：

| 測試類型 | 範例 | 數量 |
|----------|------|------|
| 產業模板 | 「我是餐飲業，幫我建進銷存」→ 產生的 schema 包含必要 tables 和 relations | 每產業 10 筆 |
| 自由描述 | 「我需要管理客戶、訂單和付款」→ schema 欄位型別正確、關聯合理 | 30 筆 |
| 修改請求 | 「加一個手機欄位」→ 正確生成 ALTER TABLE migration | 20 筆 |

### 驗證方式

1. 人工定義每個測試案例的預期 schema（必須包含的 tables/columns/relations）
2. 將自然語言描述餵入 Build Engine
3. 比對產出的 schema 是否包含所有預期項目
4. 執行 migration 確認 SQL 語法正確
5. 載入 CRUD UI 確認頁面可正常操作

## API Endpoint 清單

| Method | Path | 說明 |
|--------|------|------|
| `POST` | `/api/build` | 送出建構需求，回傳預覽或釐清問題 |
| `POST` | `/api/build/confirm` | 確認執行建構（僅 admin） |
| `GET` | `/api/build/preview/{uuid}` | 取得 schema 預覽資料 |
| `GET` | `/api/build/status/{uuid}` | 建構進度查詢（polling 用） |
| `GET` | `/api/{module}` | 動態 CRUD 列表 |
| `POST` | `/api/{module}` | 動態 CRUD 新增 |
| `GET` | `/api/{module}/{id}` | 動態 CRUD 單筆 |
| `PUT` | `/api/{module}/{id}` | 動態 CRUD 更新 |
| `DELETE` | `/api/{module}/{id}` | 動態 CRUD 刪除（soft delete） |
| `GET` | `/api/{module}/schema` | 取得動態表單欄位定義 |
| `GET` | `/api/admin/schema-versions` | Schema 版本列表（admin） |
| `POST` | `/api/admin/schema-versions/{id}/rollback` | 回退到指定版本（admin） |
