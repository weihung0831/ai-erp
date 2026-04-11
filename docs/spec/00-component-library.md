# Blade 元件庫規格

日期：2026-04-11
狀態：已核准
依據：[UI 設計規範](../design/ui-design-spec.md) / [設計模式](../design/design-pattern.md)

## 總覽

所有 UI 以 Blade Component 封裝，每個元件獨立、可重複使用、接受 props。視覺規範依循 [UI 設計規範](../design/ui-design-spec.md)。

## 進度追蹤

### Phase 1 基礎元件（與 Phase 1 前端同步開發）

**Layout**
- [x] `<x-layout.page>` — 頁面框架
- [x] `<x-layout.sidebar>` — 側邊導覽列
- [x] `<x-layout.header>` — 頂部導覽列

**Chat**
- [x] `<x-chat.bubble>` — 聊天氣泡
- [x] `<x-chat.input>` — 聊天輸入框
- [x] `<x-chat.quick-actions>` — 快捷按鈕列
- [x] `<x-chat.confidence>` — 信心度標籤
- [x] `<x-chat.typing>` — AI 打字中動畫
- [x] `<x-chat.result-table>` — 查詢結果表格（聊天內嵌）
- [x] `<x-chat.result-number>` — 查詢結果數字（聊天內嵌）

**Data**
- [x] `<x-data.table>` — 通用資料表格
- [x] `<x-data.pagination>` — 分頁元件
- [x] `<x-data.empty-state>` — 空資料提示

**Form**
- [x] `<x-form.input>` — 文字輸入框

**UI**
- [x] `<x-ui.button>` — 按鈕
- [x] `<x-ui.alert>` — 提示訊息
- [x] `<x-ui.modal>` — Modal 對話框（用於閒置警告）
- [x] `<x-ui.dropdown>` — 下拉選單（用於 header 使用者選單）
- [x] `<x-ui.loading>` — 載入動畫（skeleton）
- [x] `<x-ui.toast>` — 即時通知（操作回饋）
- [x] `<x-ui.tooltip>` — hover 提示

### Phase 2 動態 CRUD 元件

**Build**
- [x] `<x-build.module-card>` — 模組預覽卡片
- [x] `<x-build.schema-preview>` — Schema 預覽
- [x] `<x-build.column-row>` — 欄位定義行
- [x] `<x-build.industry-picker>` — 產業選擇器
- [x] `<x-build.module-checklist>` — 模組勾選清單
- [x] `<x-build.confirm-dialog>` — 建構確認對話框

**CRUD**
- [x] `<x-crud.dynamic-table>` — 動態 CRUD 表格
- [x] `<x-crud.dynamic-form>` — 動態 CRUD 表單

**Form 補充**
- [x] `<x-form.textarea>` — 多行文字
- [x] `<x-form.select>` — 下拉選單
- [x] `<x-form.toggle>` — 開關
- [x] `<x-form.date-picker>` — 日期選擇器
- [x] `<x-form.checkbox>` — 勾選框

**UI 補充**
- [x] `<x-ui.badge>` — 標籤

### Phase 3 SaaS 管理元件

**Onboarding**
- [x] `<x-onboarding.step>` — Onboarding 步驟容器
- [x] `<x-onboarding.progress>` — 進度指示器

**Billing**
- [x] `<x-billing.plan-card>` — 方案卡片
- [x] `<x-billing.usage-bar>` — 用量進度條

**Admin**
- [x] `<x-admin.tenant-card>` — 租戶資訊卡片
- [x] `<x-admin.trend-chart>` — 趨勢圖表

**Data 補充**
- [x] `<x-data.stat-card>` — 數據統計卡片

## 元件介面定義

### Layout

#### `<x-layout.page>`

頁面框架，包含側邊欄和主內容區。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| title | string | 是 | — | 頁面標題（顯示在 header） |

```blade
<x-layout.page title="聊天查詢">
    {{-- 主內容 --}}
</x-layout.page>
```

#### `<x-layout.sidebar>`

動態側邊導覽列，根據 schema_metadata 自動產生選單項目。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| items | array | 是 | — | 選單項目 `[['label' => '客戶', 'url' => '/customers', 'icon' => 'users']]` |
| active | string | 否 | null | 目前選取的 URL |

#### `<x-layout.header>`

頂部導覽列，顯示公司名稱和使用者資訊。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| company | string | 是 | — | 公司名稱 |
| user | string | 是 | — | 使用者名稱 |

### Chat

#### `<x-chat.bubble>`

聊天氣泡，依 type 決定樣式。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| type | string | 是 | — | `ai` / `user` / `system` |
| confidence | float | 否 | null | 信心度分數（僅 AI 訊息） |
| sql | string | 否 | null | 產生的 SQL（中信心時顯示） |

```blade
<x-chat.bubble type="ai" :confidence="0.87" sql="SELECT SUM(amount) ...">
    本月營收為 NT$1,234,567
</x-chat.bubble>
```

#### `<x-chat.input>`

聊天輸入框，Enter 送出、Shift+Enter 換行。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| placeholder | string | 否 | `請輸入您的問題...` | 佔位文字 |
| disabled | bool | 否 | false | 是否停用 |

Alpine.js 事件：`@submit` 觸發 `$store.chat.send()`

#### `<x-chat.quick-actions>`

快捷按鈕列。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| actions | array | 是 | — | `['本月營收', '庫存狀況', '應收帳款']` |

點擊後將文字填入輸入框並自動送出。

#### `<x-chat.confidence>`

信心度標籤。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| score | float | 是 | — | 0-1 的信心度分數 |

自動根據分數顯示對應顏色和文字（高/中/低）。

#### `<x-chat.typing>`

AI 打字中動畫（三個跳動的點）。無 props。

#### `<x-chat.result-table>`

查詢結果表格，內嵌在聊天氣泡中。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| headers | array | 是 | — | 欄位標題 |
| rows | array | 是 | — | 資料列 |
| sortable | bool | 否 | true | 是否可排序 |

#### `<x-chat.result-number>`

查詢結果數字，大字呈現。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| value | number | 是 | — | 數字值 |
| label | string | 是 | — | 標籤（「本月營收」） |
| format | string | 否 | `currency` | `currency` / `number` / `percent` |
| compare | float | 否 | null | 比較值（顯示 ▲/▼ 百分比） |

### Data

#### `<x-data.table>`

通用資料表格（CRUD 列表頁用）。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| headers | array | 是 | — | `[['key' => 'name', 'label' => '客戶名稱', 'sortable' => true]]` |
| rows | array | 是 | — | 資料列 |
| actions | array | 否 | `['edit', 'delete']` | 每列的操作按鈕 |
| searchable | bool | 否 | true | 是否顯示搜尋框 |

#### `<x-data.stat-card>`

數據統計卡片。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| label | string | 是 | — | 標籤 |
| value | string | 是 | — | 顯示值（已格式化） |
| trend | float | 否 | null | 趨勢百分比（正/負） |

#### `<x-data.pagination>`

分頁元件。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| total | int | 是 | — | 總筆數 |
| perPage | int | 否 | 15 | 每頁筆數 |
| currentPage | int | 是 | — | 目前頁碼 |

#### `<x-data.empty-state>`

空資料提示。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| message | string | 否 | `目前沒有資料` | 提示文字 |
| action | string | 否 | null | CTA 按鈕文字 |
| actionUrl | string | 否 | null | CTA 連結 |

### Form

#### `<x-form.input>`

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| name | string | 是 | — | 欄位名稱 |
| label | string | 是 | — | 欄位標籤 |
| type | string | 否 | `text` | `text` / `email` / `password` / `number` |
| required | bool | 否 | false | 是否必填 |
| error | string | 否 | null | 錯誤訊息 |
| value | string | 否 | null | 預設值 |

#### `<x-form.select>`

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| name | string | 是 | — | 欄位名稱 |
| label | string | 是 | — | 欄位標籤 |
| options | array | 是 | — | `[['value' => 'active', 'label' => '啟用']]` |
| selected | string | 否 | null | 已選值 |

#### `<x-form.toggle>`

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| name | string | 是 | — | 欄位名稱 |
| label | string | 是 | — | 欄位標籤 |
| checked | bool | 否 | false | 是否開啟 |

### UI

#### `<x-ui.button>`

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| variant | string | 否 | `primary` | `primary` / `secondary` / `text` / `danger` |
| size | string | 否 | `md` | `sm` / `md` / `lg` |
| disabled | bool | 否 | false | 是否停用 |
| loading | bool | 否 | false | 是否顯示載入中 |

#### `<x-ui.badge>`

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| variant | string | 否 | `default` | `default` / `success` / `warning` / `danger` |

#### `<x-ui.modal>`

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| title | string | 是 | — | Modal 標題 |
| show | string | 是 | — | Alpine.js 變數名稱（控制顯示） |
| maxWidth | string | 否 | `md` | `sm` / `md` / `lg` |

#### `<x-ui.alert>`

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| type | string | 否 | `info` | `info` / `success` / `warning` / `error` |
| dismissible | bool | 否 | true | 是否可關閉 |

#### `<x-ui.loading>`

頁面或區塊載入中的佔位元件（skeleton shimmer）。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| type | string | 否 | `block` | `block`（矩形）/ `line`（文字行）/ `circle`（圓形頭像） |
| height | string | 否 | `16px` | 高度 |
| width | string | 否 | `100%` | 寬度 |

#### `<x-ui.dropdown>`

下拉選單（導覽列的使用者選單等）。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| align | string | 否 | `right` | `left` / `right` |
| width | string | 否 | `200px` | 選單寬度 |

使用 `{{ $trigger }}` slot 定義觸發按鈕，`{{ $slot }}` 定義選單內容。

#### `<x-ui.toast>`

操作結果即時通知（右上角）。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| type | string | 否 | `success` | `success` / `error` / `info` |
| message | string | 是 | — | 通知文字 |
| duration | int | 否 | 3000 | 自動消失毫秒數 |

#### `<x-ui.tooltip>`

hover 提示文字。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| text | string | 是 | — | 提示文字 |
| position | string | 否 | `top` | `top` / `bottom` / `left` / `right` |

### Build 元件（Phase 2）

#### `<x-build.module-card>`

模組預覽卡片，展示一個 table 的 schema 摘要。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| name | string | 是 | — | Table 名稱 |
| displayName | string | 是 | — | 中文名稱 |
| columns | array | 是 | — | 欄位清單 |
| relations | array | 否 | `[]` | 關聯清單 |

#### `<x-build.schema-preview>`

完整 schema 預覽，包含多個 module-card。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| tables | array | 是 | — | `[{name, displayName, columns, relations}]` |

#### `<x-build.column-row>`

單一欄位定義行（在 module-card 內使用）。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| name | string | 是 | — | 欄位名稱 |
| displayName | string | 是 | — | 中文名稱 |
| type | string | 是 | — | 型別（string / integer / decimal 等） |
| required | bool | 否 | false | 是否必填 |

#### `<x-build.industry-picker>`

產業選擇器，顯示可選產業列表。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| industries | array | 是 | — | `[{id, name, icon}]` |
| selected | string | 否 | null | 已選產業 ID |

#### `<x-build.module-checklist>`

模組勾選清單，顯示產業推薦的模組。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| modules | array | 是 | — | `[{name, displayName, description, recommended}]` |
| selected | array | 否 | `[]` | 已勾選的模組名稱 |

#### `<x-build.confirm-dialog>`

建構確認對話框。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| tables | array | 是 | — | 將要建立的 table 清單 |
| show | string | 是 | — | Alpine.js 變數名稱 |

### CRUD 元件（Phase 2）

#### `<x-crud.dynamic-table>`

動態 CRUD 列表表格，根據 schema_metadata 渲染。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| schema | array | 是 | — | `schema_metadata` 欄位定義 |
| rows | array | 是 | — | 資料列 |
| actions | array | 否 | `['edit', 'delete']` | 每列操作按鈕 |
| module | string | 是 | — | 模組名稱（用於 API 路徑） |

#### `<x-crud.dynamic-form>`

動態 CRUD 表單，根據 schema_metadata 渲染對應的表單元件。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| schema | array | 是 | — | `schema_metadata` 欄位定義 |
| values | array | 否 | `[]` | 編輯時的現有值 |
| module | string | 是 | — | 模組名稱（用於 API 路徑） |
| isEdit | bool | 否 | false | 是否為編輯模式 |

### Onboarding 元件（Phase 3）

#### `<x-onboarding.step>`

Onboarding 步驟容器。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| number | int | 是 | — | 步驟編號 |
| title | string | 是 | — | 步驟標題 |
| active | bool | 否 | false | 是否為目前步驟 |

#### `<x-onboarding.progress>`

進度指示器。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| current | int | 是 | — | 目前步驟 |
| total | int | 是 | — | 總步驟數 |

### Billing 元件（Phase 3）

#### `<x-billing.plan-card>`

訂閱方案卡片。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| name | string | 是 | — | 方案名稱 |
| price | string | 是 | — | 價格文字 |
| features | array | 是 | — | 功能清單 |
| recommended | bool | 否 | false | 是否標記「推薦」 |
| current | bool | 否 | false | 是否為目前方案 |

#### `<x-billing.usage-bar>`

用量進度條。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| label | string | 是 | — | 標籤（「查詢次數」） |
| used | int | 是 | — | 已使用量 |
| limit | int | 是 | — | 上限 |
| warning | float | 否 | 0.8 | 警告閾值（百分比） |

### Admin 元件（Phase 3）

#### `<x-admin.tenant-card>`

租戶資訊卡片。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| tenant | array | 是 | — | 租戶資料（名稱、產業、方案、狀態） |

#### `<x-admin.trend-chart>`

趨勢圖表（Chart.js 封裝）。

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| labels | array | 是 | — | X 軸標籤（日期） |
| datasets | array | 是 | — | `[{label: '查詢量', data: [100, 120, ...], color: '#c96442'}]` |
| type | string | 否 | `line` | `line` / `bar` |
| target | float | 否 | null | 目標水平虛線值 |

### Form 補充定義

#### `<x-form.textarea>`

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| name | string | 是 | — | 欄位名稱 |
| label | string | 是 | — | 欄位標籤 |
| rows | int | 否 | 4 | 顯示行數 |
| required | bool | 否 | false | 是否必填 |
| error | string | 否 | null | 錯誤訊息 |
| value | string | 否 | null | 預設值 |

#### `<x-form.date-picker>`

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| name | string | 是 | — | 欄位名稱 |
| label | string | 是 | — | 欄位標籤 |
| type | string | 否 | `date` | `date` / `datetime` / `daterange` |
| required | bool | 否 | false | 是否必填 |
| value | string | 否 | null | 預設值（YYYY-MM-DD） |
| min | string | 否 | null | 最早日期 |
| max | string | 否 | null | 最晚日期 |

#### `<x-form.checkbox>`

| Prop | 型別 | 必填 | 預設 | 說明 |
|------|------|------|------|------|
| name | string | 是 | — | 欄位名稱 |
| label | string | 是 | — | 欄位標籤 |
| checked | bool | 否 | false | 是否勾選 |
