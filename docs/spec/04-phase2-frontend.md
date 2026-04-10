# 前端規格書：Phase 2 — Chat-to-Build 介面

日期：2026-04-11
狀態：已核准
依據：[Phase 2 後端規格](03-phase2-backend.md) / [元件庫](00-component-library.md) / [UI 設計規範](../design/ui-design-spec.md)

## 進度追蹤

### 頁面
- [ ] 建構聊天頁
- [ ] Schema 預覽頁
- [ ] 動態 CRUD 列表頁
- [ ] 動態 CRUD 表單頁
- [ ] Schema 版本管理頁（admin）

### 元件
- [ ] `<x-build.module-card>` — 模組預覽卡片
- [ ] `<x-build.schema-preview>` — Schema 預覽表格
- [ ] `<x-build.column-row>` — 欄位定義行
- [ ] `<x-build.industry-picker>` — 產業選擇器
- [ ] `<x-build.module-checklist>` — 模組勾選清單
- [ ] `<x-build.confirm-dialog>` — 建構確認對話框
- [ ] `<x-crud.dynamic-table>` — 動態 CRUD 表格
- [ ] `<x-crud.dynamic-form>` — 動態 CRUD 表單

### Alpine.js Store
- [ ] `buildStore`
- [ ] `sidebarStore`（動態更新導覽列）

## 頁面清單

### 1. 建構聊天頁

**路由：** `GET /build`
**Web Controller：** `Web\BuildPageController@index`
**用途：** 客戶用對話建構 ERP 模組

**頁面結構：**
```
<x-layout.page title="建構系統">
    ├── <x-layout.sidebar>
    ├── 主內容區
    │   ├── 對話歷史區域
    │   │   ├── AI 引導訊息（「你想管理什麼？」）
    │   │   ├── <x-chat.bubble>                // 對話
    │   │   ├── <x-build.industry-picker>      // 產業選擇（AI 提問時嵌入）
    │   │   ├── <x-build.module-checklist>     // 模組勾選（AI 推薦時嵌入）
    │   │   └── <x-build.schema-preview>       // Schema 預覽（AI 產生後嵌入）
    │   └── <x-chat.input>
</x-layout.page>
```

**與 Phase 1 聊天頁的差異：**
- 同樣的聊天介面框架，但對話中會嵌入互動元件（產業選擇器、模組勾選、schema 預覽）
- AI 回應的 `type` 多了 `build_preview`、`industry_select`、`module_suggest`
- 確認建構時顯示 `<x-build.confirm-dialog>`

**Alpine.js Store（buildStore）：**
- `messages[]` — 建構對話歷史
- `industry` — 已選產業
- `selectedModules[]` — 已勾選模組
- `preview` — Schema 預覽資料
- `building` — 是否正在建構中
- `buildProgress` — 多模組建構時的進度資料
- `send()` — 送出訊息，呼叫 `POST /api/build`
- `confirm()` — 確認建構，呼叫 `POST /api/build/confirm`，然後啟動 polling
- `pollBuildStatus(uuid)` — 每 2 秒呼叫 `GET /api/build/status/{uuid}`，更新進度直到完成

**串接 API：**
- `POST /api/build` → 送出需求描述，取得預覽或釐清問題
- `POST /api/build/confirm` → 確認執行建構
- `GET /api/build/status/{uuid}` → polling 建構進度（多模組時顯示每個模組的狀態）
- 建構完成後自動更新 `sidebarStore`，導覽列出現新模組

### 2. Schema 預覽頁

**路由：** `GET /preview/build/{uuid}`
**Web Controller：** `Web\BuildPageController@preview`
**用途：** 預覽 AI 產生的 schema 和模擬 UI

**頁面結構：**
```
<x-layout.page title="預覽">
    ├── 預覽標題 + 「預覽模式」浮水印
    ├── Tab 切換
    │   ├── Schema 檢視
    │   │   └── 每個 table 一張 <x-build.module-card>
    │   │       ├── Table 名稱（中文）
    │   │       ├── <x-build.column-row> × N
    │   │       └── 關聯說明
    │   └── UI 預覽
    │       └── 模擬的 CRUD 頁面（read-only，假資料）
    ├── 操作按鈕
    │   ├── <x-ui.button variant="primary"> 確認建構
    │   ├── <x-ui.button variant="secondary"> 修改
    │   └── <x-ui.button variant="text"> 取消
</x-layout.page>
```

**串接 API：**
- `GET /api/build/preview/{uuid}` → 取得 Schema 預覽資料
- `POST /api/build/confirm` → 確認建構

### 3. 動態 CRUD 列表頁

**路由：** `GET /{module}`（動態路由，例如 `/customers`、`/orders`）
**Web Controller：** `Web\DynamicCrudController@index`
**用途：** Chat-to-build 產生的模組列表頁

**頁面結構：**
```
<x-layout.page :title="$module->display_name">
    ├── 標題列
    │   ├── 模組名稱
    │   └── <x-ui.button> 新增
    ├── <x-crud.dynamic-table>
    │   ├── 欄位根據 schema_metadata 動態產生
    │   ├── 搜尋框
    │   ├── 每列操作：編輯、刪除
    │   └── <x-data.pagination>
    └── <x-data.empty-state>（無資料時）
</x-layout.page>
```

**串接 API：**
- `GET /api/{module}?page=1&search=xxx` → 取得列表資料
- `DELETE /api/{module}/{id}` → 刪除（soft delete）

### 4. 動態 CRUD 表單頁

**路由：** `GET /{module}/create` 和 `GET /{module}/{id}/edit`
**Web Controller：** `Web\DynamicCrudController@create` / `edit`
**用途：** Chat-to-build 產生的模組新增/編輯頁

**頁面結構：**
```
<x-layout.page :title="$isEdit ? '編輯' : '新增' . $module->display_name">
    ├── 表單（欄位根據 schema_metadata 動態產生）
    │   ├── string → <x-form.input>
    │   ├── text → <x-form.textarea>
    │   ├── integer/decimal → <x-form.input type="number">
    │   ├── boolean → <x-form.toggle>
    │   ├── date → <x-form.date-picker>
    │   ├── enum → <x-form.select>
    │   └── 關聯欄位 → <x-form.select>（載入關聯 table 資料）
    ├── <x-ui.button variant="primary"> 儲存
    └── <x-ui.button variant="text"> 取消
</x-layout.page>
```

**串接 API：**
- `GET /api/{module}/schema` → 取得表單欄位定義
- `GET /api/{module}/{id}` → 取得編輯資料
- `POST /api/{module}` → 新增
- `PUT /api/{module}/{id}` → 更新

### 5. Schema 版本管理頁（admin）

**路由：** `GET /admin/schema-versions`
**Web Controller：** `Web\AdminController@schemaVersions`
**用途：** 管理員查看和回退 schema 版本

**頁面結構：**
```
<x-layout.page title="Schema 版本管理">
    ├── <x-data.table>
    │   ├── 欄位：版本號、變更類型、變更內容、觸發對話、時間
    │   └── 操作：查看詳情、回退到此版本
    ├── <x-data.pagination>
    └── <x-ui.modal>（回退確認）
        ├── 回退影響預覽
        └── 確認/取消按鈕
</x-layout.page>
```

**串接 API：**
- `GET /api/admin/schema-versions?page=1`
- `POST /api/admin/schema-versions/{id}/rollback`

## 動態元件渲染規則

Chat-to-build 產生的 CRUD 頁面，欄位和元件根據 `schema_metadata` 動態決定：

| schema_metadata.type | 列表元件 | 表單元件 |
|----------------------|----------|----------|
| string | 文字 | `<x-form.input>` |
| text | 截斷文字（hover 全文） | `<x-form.textarea>` |
| integer | 數字 | `<x-form.input type="number">` |
| decimal | NT$ 格式化 | `<x-form.input type="number" step="0.01">` |
| boolean | `<x-ui.badge>` 勾/叉 | `<x-form.toggle>` |
| date | 格式化日期 | `<x-form.date-picker>` |
| datetime | 格式化日期時間 | `<x-form.date-picker>` |
| enum | `<x-ui.badge>` | `<x-form.select>` |

## 動態路由衝突避免

`GET /{module}` 為動態路由，需避免與固定路由衝突：

- 固定路由（`/chat`、`/build`、`/login`、`/admin/*`、`/account/*`）在 `routes/web.php` 中優先註冊
- 動態路由最後註冊，作為 fallback
- 路由中加入檢查：module 名稱必須存在於 `schema_metadata`，否則回傳 404
- 禁止客戶建立與固定路由同名的模組（Build Engine 產生 table 名稱時檢查保留字清單）

## 預覽頁「修改」按鈕行為

點擊「修改」→ 回到建構聊天頁，帶入目前的 conversation_id，繼續對話修改 schema。不在預覽頁內直接編輯。

## 多模組建構進度

建構多個模組時，前端透過 polling 取得即時進度：每 2 秒呼叫 `GET /api/build/status/{uuid}`，顯示每個模組的狀態（排隊中/建構中/完成/失敗），不使用 WebSocket。Phase 3 的 Onboarding Step 3 共用此機制。

## 側邊欄動態更新

建構完成後，側邊欄需要即時更新：

1. `POST /api/build/confirm` 回傳新建的 `routes[]`
2. `buildStore` 更新 `sidebarStore.items`
3. 側邊欄自動出現新模組連結
4. 不需重新整理頁面
