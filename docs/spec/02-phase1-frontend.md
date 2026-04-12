# 前端規格書：AI ERP — Chat-to-Operate 介面

日期：2026-04-13（更新）
狀態：已核准
依據：[後端規格](01-phase1-backend.md) / [元件庫](00-component-library.md) / [UI 設計規範](../design/ui-design-spec.md)

## 進度追蹤

### 頁面
- [x] 登入頁
- [x] 忘記密碼頁
- [x] 重設密碼頁
- [x] Dashboard 頁（獨立頁面，登入後首頁）
- [x] 聊天頁（查詢功能）
- [x] 聊天頁（Claude 風格空狀態 + markdown 渲染）
- [ ] 聊天頁（寫入確認 UI）
- [ ] 聊天頁（檔案上傳 UI）

### 元件（使用中）
- [x] `<x-layout.page>` / `<x-layout.sidebar>` / `<x-layout.header>`
- [x] `<x-chat.bubble>` / `<x-chat.input>` / `<x-chat.confidence>` / `<x-chat.typing>` / `<x-chat.result-table>` / `<x-chat.result-number>`
- [x] `<x-form.input>`
- [x] `<x-ui.button>` / `<x-ui.alert>` / `<x-ui.dropdown>` / `<x-ui.loading>`

### 待新增元件
- [ ] `<x-chat.confirm-action>` — 寫入確認卡片
- [ ] `<x-chat.upload-preview>` — 檔案上傳預覽
- [ ] `<x-chat.file-input>` — 檔案上傳按鈕

### Alpine.js Store
- [x] `chatStore`
- [x] `authStore`

## 頁面清單

### 1. 登入頁

- **路由：** `GET /login`
- **Web Controller：** `Web\AuthPageController@login`
- **用途：** 使用者登入，成功後導向 `/dashboard`

**頁面結構：**
- `<x-ui.splash>` 全畫面 loading 動畫（登入中顯示「系統載入中」）
- 置中登入卡片（`.login-card`）
  - 標題「AI ERP 平台」+ 副標「登入您的帳號以繼續」
  - 錯誤提示（`<x-ui.alert type="error">`）
  - Email 輸入框（`<x-form.input type="email">`）
  - 密碼輸入框（`<x-form.input type="password">`）
  - 登入按鈕（`<x-ui.button variant="primary">`，email 或 password 為空時 disabled）
  - 忘記密碼連結（→ `/forgot-password`）

**Alpine.js 邏輯：**
- 登入請求搭配 2 秒最低延遲（`Promise.all([axios.post, minDelay])`），確保 splash 動畫不會閃退
- 成功：`$store.auth.setToken(token)` → `window.location.href = '/dashboard'`
- 失敗：等 minDelay 結束後才顯示錯誤訊息

**串接 API：**
- `POST /api/login` → 取得 Bearer token + user 資料

### 2. Dashboard 頁

- **路由：** `GET /dashboard`（首頁 `/` 導向此頁）
- **Web Controller：** `Web\DashboardPageController@index`
- **用途：** 業務指標總覽，登入後首頁

**頁面結構：**
```
├── 側邊欄
│   ├── Logo「AI ERP」+ 收合按鈕（«/»）
│   ├── 導覽（Dashboard [active] / 聊天）
│   └── 使用者選單（頭像 + 名稱 + 登出 dropdown）
├── 主內容區（.dash-content，max-width: 1200px）
│   ├── Header
│   │   ├── 「Dashboard」標題
│   │   ├── 副標「2026 年 4 月業務概覽」（動態日期）
│   │   └── 期間選擇器（pill 下拉：本月/本季/年度）
│   ├── 銷售概覽 section
│   │   └── stat-card grid（營收、訂單數、平均訂單金額）
│   ├── 財務狀況 section
│   │   └── stat-card grid（應收帳款、逾期應收、費用、收款）
│   └── 營運數據 section
│       └── stat-card grid（新增客戶、新增產品、採購單、庫存不足）
├── 登出確認 Modal
└── 登出 Splash 動畫
```

**Alpine.js 邏輯：**
- `stats[]` — API 回傳的全部指標（含所有期間）
- `period` — 選中期間（預設 `'本月'`），下拉切換即時篩選
- `grouped` — computed，依 `section` 分組 + 依 `period` 前綴篩選
- `vsLabel` — computed，`{ '本月': 'vs 上月', '本季': 'vs 上季', '年度': 'vs 去年' }`
- `trendText(t)` — 0 時顯示 `➡ 持平`，正負分別 ▲/▼ + 百分比
- `trendClass(t, label)` — 費用/逾期邏輯反轉（上升為壞事）
- 未登入自動跳轉 `/login`，init 時 `fetchUser()` + `GET /api/dashboard`

**串接 API：**
- `GET /api/dashboard` → 載入指標（含 label、section、trend、formatted_value、value_format）

**互動細節：**
- 期間 pill 下拉選單即時篩選所有 section 的卡片
- 趨勢文字：▲ 綠色（好）/ ▼ 紅色（壞）/ ➡ 持平（灰色 `--text-secondary`）
- 卡片頂部色條：綠色（`.is-currency`）/ 藍色（`.is-count`），hover 時變亮
- 卡片 hover：浮起 2px + `--shadow-card` + 背景變 `--bg-white`
- Loading 時顯示 6 格 skeleton 動畫（shimmer）
- 側邊欄收合狀態存 localStorage
- 登出流程：確認 Modal → Splash 動畫（最低 2 秒）→ 跳轉 `/login`

### 3. 聊天頁

- **路由：** `GET /chat`
- **Web Controller：** `Web\ChatPageController@index`
- **用途：** AI 聊天介面 — 自然語言查詢 ERP 資料

**頁面結構（雙狀態）：**

空狀態（Claude 風格居中）：
```
├── 側邊欄
│   ├── Logo「AI ERP」+ 收合按鈕
│   ├── 導覽（Dashboard / 聊天 [active]）
│   ├── 「+ 新對話」按鈕
│   ├── 歷史對話清單（捲動）
│   └── 使用者選單（登出）
├── 居中內容（.chat-welcome）
│   ├── 品牌圖示（綠色對話框 SVG，圓角方塊背景）
│   ├── 「有什麼我能幫你的？」標題（28px bold）
│   ├── 圓角輸入框（16px radius + 內嵌綠色圓形送出按鈕）
│   └── 快捷建議 pill × 3（本月營收、客戶消費、庫存不足）
├── 登出確認 Modal
└── 登出 Splash 動畫
```

對話中狀態：
```
├── 側邊欄（同上）
├── 主內容區
│   ├── 訊息列表（#chat-messages，捲動區域）
│   │   ├── 使用者氣泡（.bubble-user，品牌綠底黑字，右下小圓角）
│   │   ├── AI 氣泡（.bubble-ai，深色卡片 + 邊框，15px 字）
│   │   │   ├── 信心度 pill（高=綠 / 中=橙 / 低=紅，9999px radius）
│   │   │   ├── markdown 渲染（.prose-ai，marked.js：段落、列表、粗體、code、blockquote）
│   │   │   ├── 數字結果（.result-number，綠色 36px 大字 + 深色背景卡片）
│   │   │   ├── 表格（.data-table，品牌綠 header、hover 行高亮、tabular-nums）
│   │   │   └── SQL 展開（<details>，中信心時顯示，深底 + 圓角 + mono 字型）
│   │   └── typing 動畫（三個跳動的點）
│   ├── 「↑ 回到頂部」按鈕（捲動 > 300px 時顯示，hover 品牌綠）
│   └── 底部輸入框（.chat-bottom-form，圓角 16px + 內嵌綠色圓形送出按鈕，max-width 800px 居中）
├── 登出確認 Modal
└── 登出 Splash 動畫
```

**Alpine.js Store（chatStore，`resources/js/stores/chat.js`）：**
- `messages[]` — 對話歷史（`{ role, content, type, data, confidence, sql }`）
- `conversations[]` — sidebar 對話清單
- `input` — 目前輸入
- `loading` — 是否等待 AI 回應
- `conversationId` — 目前對話 ID
- `init()` — 有 token 時 `fetchConversations()`
- `send(message?)` — 送出訊息（支援直接傳入字串，用於快捷 pill）
- `fetchConversations()` — `GET /api/chat/history`
- `loadConversation(id)` — `GET /api/chat/history/{id}`，重建 messages
- `newConversation()` — 清空 conversationId + messages
- `deleteConversation(id)` — `DELETE /api/chat/history/{id}`，更新 sidebar
- `formatValue(value, format)` — 格式化數字（currency: NT$ 千分位、percent: %）
- `formatCell(cell, header)` — 表格 cell 格式化（header 含金額關鍵字時自動用 currency）
- `_scrollToBottom()` — `Alpine.nextTick` 捲動到最底

**Markdown 渲染：**
- `marked.js`（`resources/js/bootstrap.js` 初始化，`breaks: true, gfm: true`）
- `window.renderMarkdown(text)` → AI 氣泡用 `x-html="renderMarkdown(msg.content)"`
- `.prose-ai` CSS 覆蓋 Tailwind reset（`list-style: disc/decimal`、heading margin、code 背景）

**串接 API：**
- `POST /api/chat` → 送出訊息，取得 AI 回應（含 reply、type、data、confidence、sql）
- `GET /api/chat/history` → 載入對話清單（sidebar）
- `GET /api/chat/history/{uuid}` → 載入單一對話的所有 messages
- `DELETE /api/chat/history/{uuid}` → 刪除對話
- 寫入確認（待開發）：`POST /api/chat/confirm/{mutation_id}` / `reject/{mutation_id}`
- 檔案上傳（待開發）：`POST /api/upload/preview` / `confirm/{upload_id}` / `reject/{upload_id}`

**互動細節：**
- 空狀態：居中歡迎 + 輸入框 + 快捷 pill，送出或點 pill 後切到對話狀態
- Enter 送出（IME composing 中不觸發），Shift+Enter 換行
- 送出後自動 `_scrollToBottom()`
- AI 回應中顯示 typing 動畫（三個跳動的點）
- 回應根據 `type` 渲染：`numeric` → 數字卡片、`table` → 表格、`error` → 錯誤氣泡
- 中信心（70-95%）顯示信心度 pill + SQL 展開；低信心顯示 pill 但不展開 SQL
- 側邊欄可收合/展開（`«`/`»`），狀態存 localStorage（`sidebarOpen`）
- 對話項目 hover 時顯示 `×` 刪除按鈕
- 登出流程：sidebar 底部使用者選單 → 確認 Modal → Splash 動畫 → `/login`

### 4. 聊天頁 — 寫入確認 UI

**前置：** 聊天頁已完成查詢功能，此為擴充

**觸發條件：** API 回傳 `type: 'pending_confirmation'` 時渲染

**UI 結構：**
```
AI 氣泡
├── 操作摘要（人類可讀，非 SQL）
│   ├── 操作類型標籤（新增 / 修改 / 刪除）
│   ├── 目標表名稱
│   ├── 影響欄位與值（key-value 列表）
│   └── 預估影響行數
├── 確認按鈕（品牌綠）
├── 取消按鈕（次要灰）
└── 5 分鐘倒數提示（逾時自動失效）
```

**Alpine.js 邏輯：**
- `confirmMutation(mutationId)` → `POST /api/chat/confirm/{id}` → 顯示執行結果氣泡
- `rejectMutation(mutationId)` → `POST /api/chat/reject/{id}` → 顯示「操作已取消」
- 確認/取消後按鈕變為不可點擊，顯示已處理狀態

**串接 API：**
- `POST /api/chat/confirm/{mutation_id}` → 確認寫入，回傳 `type: 'mutation_result'`
- `POST /api/chat/reject/{mutation_id}` → 拒絕寫入

**互動細節：**
- 確認前可捲動回看摘要
- 執行結果：成功顯示綠色邊框 + 影響行數，失敗顯示紅色邊框 + 錯誤原因
- 逾時（5 分鐘）按鈕自動灰化，顯示「操作已過期，請重新下達指令」

### 5. 聊天頁 — 檔案上傳 UI

**前置：** 聊天頁已完成查詢功能，此為擴充

**觸發方式：** 輸入框旁 📎 按鈕，或拖放檔案到聊天區域

**上傳流程：**
```
1. 使用者選擇/拖放檔案（.xlsx / .csv，上限 10MB）
2. 前端驗證格式與大小 → 不符合則聊天氣泡顯示錯誤
3. POST /api/upload/preview → 取得解析預覽
4. AI 氣泡顯示上傳預覽：
│   ├── 檔案名稱 + 筆數
│   ├── 目標表名稱
│   ├── 欄位對應表（原始欄位 → DB 欄位）
│   ├── 重複資料警告（如有）
│   ├── 確認匯入按鈕（品牌綠）
│   └── 取消按鈕（次要灰）
5. 確認 → POST /api/upload/confirm/{id} → 顯示結果摘要（成功筆數 / 失敗原因）
6. 取消 → POST /api/upload/reject/{id} → 顯示「匯入已取消」
```

**Alpine.js 邏輯：**
- `uploadFile(file)` → 驗證 + `POST /api/upload/preview` → 渲染預覽氣泡
- `confirmUpload(uploadId)` → `POST /api/upload/confirm/{id}`
- `rejectUpload(uploadId)` → `POST /api/upload/reject/{id}`

**串接 API：**
- `POST /api/upload/preview` → 上傳檔案取得解析預覽
- `POST /api/upload/confirm/{upload_id}` → 確認批量匯入
- `POST /api/upload/reject/{upload_id}` → 取消匯入

**互動細節：**
- 拖放時聊天區域顯示 drop zone 高亮
- 上傳中顯示進度（檔案名稱 + spinner）
- 僅支援 `.xlsx` 和 `.csv`，編碼支援 UTF-8 和 Big5
- 超過 10MB 顯示「檔案大小超過 10MB 上限」
- 批量匯入為 transaction 包裹（全成功或全失敗）

### 6. 忘記密碼頁

- **路由：** `GET /forgot-password`
- **Web Controller：** `Web\AuthPageController@forgotPassword`
- **用途：** 忘記密碼，寄送重設連結

**頁面結構：**
- 置中卡片（共用 `.login-card` 樣式）
  - 標題「忘記密碼」+ 副標「輸入您的 Email，我們會寄送重設密碼連結」
  - 錯誤提示（`<x-ui.alert type="error">`）
  - 送出前：Email 輸入框 + 「寄送重設連結」按鈕（loading 時顯示「寄送中…」）+ 返回登入連結
  - 送出成功：`<x-ui.alert type="success">` 「重設連結已寄出，請檢查您的 Email」+ 返回登入連結

**串接 API：**
- `POST /api/forgot-password` → 寄送重設 email

### 7. 重設密碼頁

- **路由：** `GET /reset-password/{token}`
- **Web Controller：** `Web\AuthPageController@resetPassword`
- **用途：** 以 email 連結中的 token 重設密碼

**頁面結構：**
- 置中卡片（共用 `.login-card` 樣式）
  - 標題「重設密碼」+ 副標「請輸入新密碼」
  - 錯誤提示（`<x-ui.alert type="error">`）
  - 送出前：新密碼 + 確認新密碼輸入框 + 「重設密碼」按鈕（loading 時顯示「重設中…」）
  - 成功：`<x-ui.alert type="success">` 「密碼已重設成功」+ 「前往登入」按鈕
- `email` 和 `token` 由 Blade `@js()` 從 server 注入

**串接 API：**
- `POST /api/reset-password` → 重設密碼（帶 email、token、password、password_confirmation）

## Axios 設定

```javascript
// resources/js/axios.js
axios.defaults.baseURL = window.location.origin;
axios.defaults.headers.common['Accept'] = 'application/json';

// 從 localStorage 取 token
const token = localStorage.getItem('token');
if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

// 401 自動跳轉登入頁
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            localStorage.removeItem('token');
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);
```

## 閒置自動登出

- 使用 Alpine.js 設定 30 分鐘閒置計時器
- 偵測滑鼠移動、鍵盤輸入、點擊重置計時
- 到期前 2 分鐘顯示 `<x-ui.modal>` 提醒「即將自動登出」
- 使用者點擊「繼續使用」→ 呼叫 `POST /api/token/refresh` 延長 token 有效期，重置計時器
- 到期未操作 → 呼叫 `POST /api/logout` 撤銷 token，清除 localStorage，跳轉登入頁

## SSE 串流處理

聊天回應透過 Server-Sent Events 串流接收：

```javascript
async function streamChat(message) {
    const response = await fetch('/api/chat/stream', {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
        body: JSON.stringify({ message, conversation_id })
    });

    const reader = response.body.getReader();
    const decoder = new TextDecoder();
    let buffer = '';

    while (true) {
        const { done, value } = await reader.read();
        if (done) break;
        buffer += decoder.decode(value, { stream: true });
        // 逐步更新 AI 氣泡內容
        $store.chat.updateLastMessage(buffer);
    }
}
```

## 錯誤處理（前端）

| 場景 | 呈現方式 |
|------|----------|
| API 回傳 401 | 自動跳轉登入頁 |
| API 回傳 500 | 聊天氣泡顯示「系統忙碌，請稍後再試」 |
| API 逾時 | 聊天氣泡顯示「回應超時，請重試」+ 重試按鈕 |
| 網路斷線 | 頂部顯示 `<x-ui.alert type="warning">` 「網路連線中斷」 |
| 帳號鎖定（密碼錯 5 次） | 登入頁顯示 `<x-ui.alert type="error">` 「帳號已鎖定，請 15 分鐘後再試」 |
| 寫入確認逾時 | 確認按鈕變灰並顯示「操作已過期，請重新下達指令」 |
| 檔案格式不支援 | 聊天氣泡顯示「僅支援 .xlsx 和 .csv 格式」 |
| 檔案過大 | 聊天氣泡顯示「檔案大小超過 10MB 上限」 |
