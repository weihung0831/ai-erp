# 功能規格書：AI ERP 後端 — Chat-to-Operate

日期：2026-04-12（更新）
狀態：已核准
依據：[設計文件](../design/ai-erp-platform.md) / [架構文件](../architecture/system-architecture.md)

## 進度追蹤

### 查詢功能（已完成）
- [x] US-1：基本查詢
- [x] US-2：表格查詢
- [x] US-3：多輪對話
- [x] US-4：信心度提示
- [x] US-5：常見問題快捷
- [x] US-6：認證登入
- [x] US-7：敏感欄位保護
- [x] US-9：對話管理
- [x] Golden Test Suite 建立（100 筆關鍵財務 + 50 筆一般查詢）
- [x] 準確率達標（關鍵財務 > 99%，一般 > 95%）

### 寫入功能（待開發）
- [ ] US-10：基本寫入操作
- [ ] US-11：寫入確認流程
- [ ] US-12：檔案上傳批量匯入
- [x] US-13：Dashboard 指標 API
- [ ] 寫入操作 Golden Test Suite

## 目標

讓客戶透過聊天介面完成所有 ERP 操作（查詢 + 新增 + 修改 + 刪除），取代傳統 ERP 的表單和選單 UI。搭配 Dashboard 提供業務概覽，搭配檔案上傳處理批量資料匯入。

## 範圍

### 包含

- 聊天介面（輸入框 + 對話記錄 + 結果呈現）
- 自然語言轉 SQL（SELECT / INSERT / UPDATE / DELETE）
- 寫入操作確認流程（pending → confirm/reject → execute）
- 信心度分層回退機制（查詢操作）
- 使用者認證與租戶隔離
- 對話歷史紀錄
- Dashboard 指標 API
- 檔案上傳與批量匯入
- 完整 audit trail

### 初始版本不包含

- Chat-to-build（AI 自動產生 schema）
- 多語言支援（僅繁體中文）
- 行動裝置 App（僅 Web）
- DDL 操作（CREATE TABLE、ALTER TABLE 等）

## 使用者故事

### US-1：基本查詢

> 身為 ERP 使用者，我想用自然語言問「這個月營收多少」，系統直接告訴我答案，不用自己進報表系統找。

**驗收條件：**
- 輸入自然語言問題後首次回應（first token）< 3 秒
- 回應包含明確的數字和時間範圍
- 數字格式符合台灣慣例（NT$、千分位）

### US-2：表格查詢

> 身為 ERP 使用者，我想問「應收帳款超過 60 天的客戶有哪些」，系統列出表格讓我一眼看完。

**驗收條件：**
- 回應以表格形式呈現（客戶名稱、金額、逾期天數）
- 表格可排序
- 超過 10 筆時分頁

### US-3：多輪對話

> 身為 ERP 使用者，我問完「這個月營收」後想追問「跟上個月比呢」，系統能理解上下文。

**驗收條件：**
- 系統記住對話上下文，不需重複說明
- 對話歷史可捲動回看
- 開新對話時清除上下文

### US-4：信心度提示

> 身為 ERP 使用者，當系統不太確定查詢結果時，我希望它告訴我而不是給我錯的數字。

**驗收條件：**
- 高信心（> 95%）：直接顯示結果
- 中信心（70-95%）：顯示結果 + 提示「建議確認」+ 可展開 SQL
- 低信心（< 70%）：不顯示結果，引導使用者釐清問題

### US-5：常見問題快捷

> 身為 ERP 使用者，我希望系統提供常問問題的快捷按鈕，不用每次打字。

**驗收條件：**
- 聊天介面顯示 3-5 個快捷按鈕（依租戶常用查詢排序）
- 點擊後等同輸入該問題
- 快捷按鈕可由管理員自訂

### US-6：認證登入

> 身為 ERP 使用者，我需要用帳號密碼登入，確保只有我能看到我公司的資料。

**驗收條件：**
- 使用 email + 密碼登入
- 登入後只能存取所屬租戶的資料
- 閒置 30 分鐘自動登出
- 密碼錯誤 5 次鎖定帳號 15 分鐘

### US-7：敏感欄位保護

> 身為管理員，我可以標記某些欄位（如薪資、密碼）為不可查詢，AI 不會回傳這些資料。

**驗收條件：**
- 管理員可在 schema metadata 中標記欄位為 `restricted`
- Query Engine 產生的 SQL 不會 SELECT 被標記的欄位
- 使用者查詢涉及敏感欄位時，回傳「此資料受限，無法查詢」

### US-9：對話管理

> 身為 ERP 使用者，我可以查看歷史對話清單、切換回舊對話繼續聊，以及刪除不需要的對話。

**驗收條件：**
- 側邊欄顯示歷史對話清單，按最後活動時間倒序
- 點擊歷史對話可載入完整對話內容，接續對話
- 可刪除單一對話，級聯清除相關聊天紀錄
- 取得目前登入使用者資訊（`GET /api/user`），供前端顯示使用者名稱

### US-10：基本寫入操作

> 身為 ERP 使用者，我想用自然語言說「幫我新增一筆訂單，客戶 ABC，金額 12,500」，系統幫我新增到資料庫。

**驗收條件：**
- 支援 INSERT、UPDATE、DELETE 三種操作
- AI 正確解析使用者意圖並產生對應 SQL
- 產生的 SQL 中所有欄位和值符合 schema metadata 定義
- 寫入操作不直接執行，進入確認流程（見 US-11）

### US-11：寫入確認流程

> 身為 ERP 使用者，在 AI 執行寫入操作前，我要先看到操作摘要並確認，防止誤操作。

**驗收條件：**
- AI 產生寫入 SQL 後，回傳人類可讀的操作摘要（非 SQL），狀態為 `pending`
- 操作摘要包含：操作類型（新增/修改/刪除）、目標表、影響的欄位和值、預估影響行數
- 使用者確認後執行 SQL，回傳執行結果（「已新增訂單 #1234」）
- 使用者拒絕則丟棄，回傳「操作已取消」
- 確認逾時 5 分鐘自動丟棄
- 所有寫入操作（含取消的）記錄完整 audit trail

### US-12：檔案上傳批量匯入

> 身為 ERP 使用者，我想上傳一份 Excel 檔案，系統自動解析並批量匯入到正確的資料表。

**驗收條件：**
- 支援 .xlsx 和 .csv 格式，編碼支援 UTF-8 和 Big5
- 檔案大小上限 10MB
- 上傳後 AI 解析欄位並回傳預覽摘要（筆數、欄位名稱、目標表、欄位對應）
- 欄位名稱不完全匹配時，AI 建議對應（如「商品名稱」→ `product_name`）
- 使用者確認後批量執行（transaction 包裹，全成功或全失敗）
- 重複資料偵測：依據主鍵或 unique 欄位提示可能重複的行
- 執行結果摘要回饋（成功筆數 / 失敗原因）

### US-13：Dashboard 指標 API

> 身為 ERP 使用者，我想在獨立 Dashboard 頁面看到業務關鍵指標的即時概覽（營收、訂單數等），並能按月/季/年切換時間維度。

**驗收條件：**
- `GET /api/dashboard` 回傳三個 section（sales / finance / operations）的預定義指標
- 每個指標包含本月/本季/年度三個時間維度，各自附上 vs 上期趨勢百分比
- Dashboard 指標使用 Laravel Query Builder 預定義查詢（非即時 LLM 生成），確保穩定性和速度
- 回應延遲 < 1 秒

**實作摘要：**

| 檔案 | 說明 |
|------|------|
| `DashboardController` | Thin controller，從 `$request->user()->tenant_id` 取得租戶 ID |
| `DashboardService` | 預定義查詢，分 `salesMetrics` / `financeMetrics` / `operationMetrics` 三組 |
| `DashboardMetric` DTO | `label`、`section`、`valueFormat`、`value`、`formattedValue`、`trend` |
| `ValueFormat::format()` | 統一格式化（Currency → `NT$1,234,567`、Count → `1,234`） |
| `AggregationType` enum | sum / count / avg / max / min（用於 schema_metadata KPI 標記） |

**指標清單：**

| Section | 指標 | 查詢邏輯 | 趨勢比較 |
|---------|------|----------|----------|
| sales | {期間}營收 | `SUM(orders.total_amount)` WHERE `order_date` in 期間 | vs 上期 |
| sales | {期間}訂單數 | `COUNT(orders.*)` WHERE `order_date` in 期間 | vs 上期 |
| sales | {期間}平均訂單金額 | 營收 ÷ 訂單數 | vs 上期 |
| finance | {期間}應收帳款 | `SUM(accounts_receivable.amount)` WHERE `created_at` in 期間 | vs 上期 |
| finance | {期間}逾期應收 | `SUM(amount - paid_amount)` WHERE `status='overdue'` AND `due_date` in 期間 | vs 上期 |
| finance | {期間}費用 | `SUM(expenses.amount)` WHERE `expense_date` in 期間 | vs 上期 |
| finance | {期間}收款 | `SUM(payments.amount)` WHERE `payment_date` in 期間 | vs 上期 |
| operations | {期間}新增客戶 | `COUNT(customers.*)` WHERE `created_at` in 期間 | vs 上期 |
| operations | {期間}新增產品 | `COUNT(products.*)` WHERE `is_active=1` AND `created_at` in 期間 | vs 上期 |
| operations | {期間}採購單 | `COUNT(purchase_orders.*)` WHERE `order_date` in 期間 | vs 上期 |
| operations | {期間}庫存不足 | `COUNT(inventory.*)` WHERE `quantity < min_quantity`（即時快照） | 持平 |

**趨勢計算：** `(current - previous) / |previous|`；previous = 0 且 current > 0 時為 `1.0`；兩者皆 0 時為 `0.0`

**API 回應格式：**
```json
{
  "data": [
    {
      "label": "本月營收",
      "section": "sales",
      "value_format": "currency",
      "value": 231891,
      "formatted_value": "NT$231,891",
      "trend": -0.529
    }
  ]
}
```

## 聊天介面規格

### 佈局

Dashboard 為獨立頁面（`/dashboard`），聊天為獨立頁面（`/chat`），透過側邊欄導覽切換。

**聊天頁（空狀態）：**
```
┌─────────┬────────────────────────┐
│ AI ERP  │                        │
│ ─────── │     💬 品牌圖示         │
│ Dashboard│                       │
│ 聊天 ●  │  有什麼我能幫你的？      │
│ ─────── │                        │
│ + 新對話 │  [────輸入框────── ➤]   │
│         │                        │
│         │  [本月營收] [客戶消費]    │
│         │  [庫存不足]             │
│ Admin ▾ │                        │
└─────────┴────────────────────────┘
```

**聊天頁（對話中）：**
```
┌─────────┬────────────────────────┐
│ AI ERP  │                        │
│ ─────── │  [User] 幫我新增訂單    │
│ Dashboard│                       │
│ 聊天 ●  │  [AI] 即將新增以下訂單： │
│ ─────── │       客戶：ABC        │
│ + 新對話 │       金額：NT$12,500  │
│ 歷史對話 │       [確認] [取消]     │
│ ...     │                        │
│         ├────────────────────────┤
│ Admin ▾ │  [────輸入框────── ➤]   │
└─────────┴────────────────────────┘
```

### 回應類型

| 類型 | 說明 | 呈現方式 |
|------|------|----------|
| 數值 | 單一數字回答（營收、數量） | 大字數字 + 比較指標 |
| 表格 | 多筆資料列表 | 表格 + 排序 + 分頁 |
| 摘要 | 綜合分析（趨勢、比較） | 文字段落 + 重點數字高亮 |
| 釐清 | 低信心，需要更多資訊 | 引導問題 + 選項按鈕 |
| 待確認 | 寫入操作等待使用者確認 | 操作摘要 + 確認/取消按鈕 |
| 執行結果 | 寫入操作執行完成 | 成功/失敗訊息 + 影響行數 |
| 上傳預覽 | 檔案上傳解析結果 | 預覽表格 + 欄位對應 + 確認/取消 |
| 錯誤 | 無法理解或執行 | 友善錯誤訊息 + 建議改問法 |

### 互動細節

- **送出方式：** Enter 送出，Shift+Enter 換行
- **檔案上傳：** 輸入框旁的 📎 按鈕，或拖放檔案到聊天區域
- **載入狀態：** 打字動畫（三個跳動的點）
- **串流輸出：** 文字逐字顯示（透過 Laravel streaming response + Axios 接收 SSE）
- **對話長度：** 單次對話最多 50 輪，超過建議開新對話

## Operation Engine 規格

### 輸入

```php
[
    'message' => '幫我新增一筆訂單，客戶 ABC，金額 12500',  // 使用者輸入
    'conversation' => [...],               // 前幾輪對話（最多 10 輪）
    'tenant_schema' => [...],              // 該租戶的 schema metadata
    'domain_context' => '餐飲業'            // 產業別
]
```

### 處理流程

```
1. 意圖分類
   ├── 查詢類（SELECT）→ 查詢流程
   ├── 新增類（INSERT）→ 寫入流程
   ├── 修改類（UPDATE）→ 寫入流程
   ├── 刪除類（DELETE）→ 寫入流程
   ├── 閒聊類 → 友善回應，引導回操作
   └── 不明確 → 釐清問題

2. Schema Context 組裝
   ├── 載入租戶 schema_metadata（table + column 中文註解）
   ├── 載入相關 domain knowledge（產業規則）
   └── 組裝為 system prompt

3. SQL 生成（gpt-4.1-mini function calling）
   ├── function: execute_query(sql, explanation)     // 查詢
   ├── function: execute_mutation(sql, explanation, mutation_type, affected_summary)  // 寫入
   └── LLM 回傳結構化 SQL + 自然語言解釋

4. SQL 驗證
   ├── 查詢類：
   │   ├── 白名單檢查：只允許 SELECT
   │   ├── EXPLAIN 檢查：拒絕預估掃描行數 > 100,000
   │   └── 欄位存在性驗證
   ├── 寫入類：
   │   ├── 禁止 DDL（DROP / ALTER / TRUNCATE / CREATE）
   │   ├── UPDATE / DELETE 必須帶 WHERE 子句
   │   ├── 影響行數預估（EXPLAIN / SELECT COUNT）：超過 100 行需額外警告
   │   ├── 欄位存在性驗證
   │   └── 敏感欄位保護（restricted 欄位不可寫入）

5. 信心度評估（僅查詢類）
   ├── 基礎分數：LLM function calling 回傳 confidence 欄位（0-1）
   ├── 加分項：SQL 中所有欄位都存在於 schema metadata → +0.1
   ├── 減分項：使用了模糊匹配（LIKE）或假設欄位含義 → -0.1
   ├── 減分項：查詢涉及多表 JOIN → -0.05 per JOIN
   ├── 最終分類：
   │   ├── 高（> 0.95）：schema 完全匹配、無歧義
   │   ├── 中（0.70-0.95）：有模糊匹配或假設
   │   └── 低（< 0.70）：多重歧義或 schema 不匹配

6. 執行 / 確認
   ├── 查詢類：
   │   ├── 高信心 → 執行 SQL，格式化結果
   │   ├── 中信心 → 執行 SQL，格式化結果 + 附加提示
   │   └── 低信心 → 不執行，回傳釐清問題
   └── 寫入類：
       ├── 產生人類可讀操作摘要
       ├── 暫存 SQL 和 metadata，狀態設為 pending
       ├── 回傳操作摘要供使用者確認
       ├── 使用者確認 → 執行 SQL → 回傳結果
       └── 使用者拒絕 / 逾時 → 丟棄
```

### 查詢輸出

```php
[
    'reply' => '本月營收為 NT$1,234,567',
    'confidence' => 0.97,
    'type' => 'numeric',
    'data' => [
        'value' => 1234567,
        'currency' => 'TWD',
        'period' => '2026-04',
    ],
    'sql' => 'SELECT SUM(...) FROM ...',
    'tokens_used' => 1847,
]
```

### 寫入待確認輸出

```php
[
    'reply' => '即將新增以下訂單：\n客戶：ABC\n金額：NT$12,500\n日期：2026/04/12',
    'type' => 'pending_confirmation',
    'mutation_id' => 'mut_abc123',           // 用於確認/拒絕
    'mutation_type' => 'insert',             // insert / update / delete
    'affected_table' => 'orders',
    'affected_rows_estimate' => 1,
    'sql' => 'INSERT INTO orders ...',       // 前端不直接顯示，供 debug
    'tokens_used' => 2103,
]
```

### 寫入執行結果輸出

```php
[
    'reply' => '已新增訂單 #1234',
    'type' => 'mutation_result',
    'success' => true,
    'affected_rows' => 1,
    'mutation_type' => 'insert',
]
```

## Schema Metadata 管理

### 初始建立

團隊手動為客戶的 DB 建立 schema metadata：

1. 連接客戶的 MySQL DB
2. 讀取 `INFORMATION_SCHEMA.COLUMNS`
3. 為每個 table/column 加上中文 `display_name` 和 `description`
4. 標記敏感欄位（薪資、密碼）為不可查詢/寫入
5. 定義 table 間的關聯關係
6. 標記 KPI 欄位（`is_kpi: true`）供 Dashboard 使用

### 儲存位置

存在租戶 DB 的 `schema_metadata` 表中，Operation Engine 每次操作時載入。

## 準確率驗證

### Golden Test Suite

#### 查詢測試（已完成，150 筆）

| 難度 | 範例 | 關鍵財務 | 一般查詢 |
|------|------|----------|----------|
| 簡單 | 「目前有多少客戶？」（單表 COUNT） | 30 筆 | 20 筆 |
| 中等 | 「本月營收多少？」（JOIN + SUM + 日期篩選） | 50 筆 | 20 筆 |
| 困難 | 「應收超過 60 天且金額 > 10 萬的客戶，按金額排序」 | 20 筆 | 10 筆 |

#### 寫入測試（待建立）

| 類型 | 範例 | 筆數 |
|------|------|------|
| INSERT | 「新增一筆訂單，客戶 ABC，金額 12,500」 | 20 筆 |
| UPDATE | 「把客戶 ABC 的電話改成 0912345678」 | 15 筆 |
| DELETE | 「刪除訂單 #1234」 | 10 筆 |
| 邊界/安全 | 「刪除所有訂單」（應被擋下）、「修改全部客戶的折扣」（應警告影響行數） | 15 筆 |

### 驗證流程

1. 人工確認預期操作結果
2. 將自然語言指令餵入 Operation Engine
3. 比對 AI 產生的操作摘要與預期結果
4. 寫入類：驗證確認後 DB 狀態是否正確
5. 每次修改 prompt 或 schema 後重跑

### 達標標準

- 查詢：關鍵財務數據 > 99%，一般查詢 > 95%
- 寫入：操作摘要與使用者意圖一致率 > 90%

## 非功能需求

| 項目 | 要求 |
|------|------|
| 回應延遲 | 首次回應（first token）< 3 秒，完整回應 < 10 秒 |
| 並行使用者 | 每租戶至少 5 人同時使用 |
| 對話歷史保留 | 90 天 |
| Audit trail 保留 | 永久（寫入操作記錄） |
| 可用性 | 99.5% uptime |
| 瀏覽器支援 | Chrome、Edge、Safari（最近 2 個版本） |
| 檔案上傳 | 最大 10MB，支援 .xlsx / .csv |

## 錯誤處理

| 場景 | 行為 |
|------|------|
| LLM API 逾時（> 10 秒） | 顯示「系統忙碌，請稍後再試」，不重試 |
| LLM API 連續失敗 | 第 2 次失敗後顯示「AI 服務暫時無法使用，請聯繫管理員」 |
| 租戶 DB 連線失敗 | 顯示「無法連接資料庫，請聯繫管理員」 |
| SQL 執行逾時（> 3 秒） | 顯示「查詢資料量過大，請縮小範圍」 |
| OpenAI token 額度用盡 | 顯示「本月查詢額度已用完」，記錄 alert 通知管理員 |
| 使用者查詢敏感欄位 | 顯示「此資料受限，無法查詢」 |
| 寫入 SQL 違反約束（unique / FK） | 回報具體錯誤原因，不做部分寫入，整筆 rollback |
| 批量匯入部分失敗 | transaction 包裹，全成功或全失敗，回報失敗行數和原因 |
| Audit trail 寫入失敗 | 操作本身仍執行但記錄告警，由系統通知團隊補登 |
| 寫入確認逾時（5 分鐘） | 自動丟棄 pending 操作，通知使用者「操作已過期，請重新下達指令」 |

## API Endpoint 清單

### 認證

| Method | Path | 說明 |
|--------|------|------|
| `POST` | `/api/login` | 登入，回傳 Sanctum token |
| `POST` | `/api/logout` | 登出，撤銷 token |
| `GET` | `/api/user` | 取得目前登入使用者資訊 |
| `POST` | `/api/token/refresh` | 延長 token 有效期 |
| `POST` | `/api/forgot-password` | 寄送密碼重設 email |
| `POST` | `/api/reset-password` | 重設密碼 |

### 聊天操作

| Method | Path | 說明 |
|--------|------|------|
| `POST` | `/api/chat` | 送出聊天訊息，回傳 AI 回應（查詢或待確認寫入） |
| `POST` | `/api/chat/stream` | 串流聊天回應（SSE） |
| `POST` | `/api/chat/confirm/{mutation_id}` | 確認寫入操作 |
| `POST` | `/api/chat/reject/{mutation_id}` | 拒絕寫入操作 |
| `GET` | `/api/chat/history` | 取得對話歷史清單 |
| `GET` | `/api/chat/history/{uuid}` | 取得單一對話的所有 messages |
| `DELETE` | `/api/chat/history/{uuid}` | 刪除對話 |

### 檔案上傳

| Method | Path | 說明 |
|--------|------|------|
| `POST` | `/api/upload/preview` | 上傳檔案並回傳解析預覽（欄位對應、筆數、目標表） |
| `POST` | `/api/upload/confirm/{upload_id}` | 確認匯入 |
| `POST` | `/api/upload/reject/{upload_id}` | 取消匯入 |

### Dashboard

| Method | Path | 說明 |
|--------|------|------|
| `GET` | `/api/dashboard` | 取得 Dashboard 指標清單（預定義查詢，含 section 分組、月/季/年趨勢） |

### 管理

| Method | Path | 說明 |
|--------|------|------|
| `GET` | `/api/admin/schema-fields` | 取得欄位列表 |
| `PATCH` | `/api/admin/schema-fields/{table}/{column}` | 切換欄位 restricted / is_kpi 狀態 |
