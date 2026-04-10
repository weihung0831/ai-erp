# 前端規格書：Phase 1 — Chat-to-Query 介面

日期：2026-04-11
狀態：已核准
依據：[Phase 1 後端規格](01-phase1-backend.md) / [元件庫](00-component-library.md) / [UI 設計規範](../design/ui-design-spec.md)

## 進度追蹤

### 頁面
- [ ] 登入頁
- [ ] 忘記密碼頁
- [ ] 重設密碼頁
- [ ] 聊天頁（主頁面）
- [ ] 查詢日誌頁（admin）
- [ ] 快捷按鈕管理頁（admin）
- [ ] 敏感欄位管理頁（admin）

### 元件（使用中）
- [ ] `<x-layout.page>` / `<x-layout.sidebar>` / `<x-layout.header>`
- [ ] `<x-chat.bubble>` / `<x-chat.input>` / `<x-chat.quick-actions>` / `<x-chat.confidence>` / `<x-chat.typing>` / `<x-chat.result-table>` / `<x-chat.result-number>`
- [ ] `<x-data.table>` / `<x-data.pagination>` / `<x-data.empty-state>`
- [ ] `<x-form.input>`
- [ ] `<x-ui.button>` / `<x-ui.alert>` / `<x-ui.modal>` / `<x-ui.dropdown>` / `<x-ui.loading>` / `<x-ui.toast>` / `<x-ui.tooltip>`

### Alpine.js Store
- [ ] `chatStore`
- [ ] `authStore`

## 頁面清單

### 1. 登入頁

**路由：** `GET /login`
**Web Controller：** `Web\AuthPageController@login`
**用途：** 使用者登入

**頁面結構：**
- 置中登入卡片
- Email 輸入框（`<x-form.input>`）
- 密碼輸入框（`<x-form.input type="password">`）
- 登入按鈕（`<x-ui.button variant="primary">`）
- 錯誤提示（`<x-ui.alert type="error">`）
- 忘記密碼連結（→ `/forgot-password`）

**Alpine.js 邏輯：**
```javascript
x-data="{
    email: '',
    password: '',
    error: null,
    loading: false,
    async login() {
        this.loading = true;
        try {
            const res = await axios.post('/api/login', { email: this.email, password: this.password });
            localStorage.setItem('token', res.data.token);
            window.location.href = '/chat';
        } catch (e) {
            this.error = e.response?.data?.message || '登入失敗';
        } finally {
            this.loading = false;
        }
    }
}"
```

**串接 API：**
- `POST /api/login` → 取得 Bearer token

### 2. 聊天頁（主頁面）

**路由：** `GET /chat`
**Web Controller：** `Web\ChatPageController@index`
**用途：** Chat-to-query 主介面

**頁面結構：**
```
<x-layout.page title="聊天查詢">
    ├── <x-layout.sidebar>         // 左側導覽
    ├── 主內容區
    │   ├── 對話歷史區域            // 捲動區域
    │   │   ├── 歡迎訊息           // AI 初始訊息
    │   │   ├── <x-chat.bubble>    // 逐筆渲染對話
    │   │   └── <x-chat.typing>    // AI 回應中
    │   ├── <x-chat.quick-actions> // 快捷按鈕
    │   └── <x-chat.input>         // 輸入框
</x-layout.page>
```

**Alpine.js Store（chatStore）：**
- `messages[]` — 對話歷史
- `input` — 目前輸入
- `loading` — 是否等待 AI 回應
- `conversationId` — 目前對話 ID
- `send()` — 送出訊息，呼叫 `POST /api/chat`
- `newConversation()` — 開新對話

**串接 API：**
- `POST /api/chat` → 送出訊息，取得 AI 回應
- `GET /api/chat/history?conversation_id=xxx` → 載入歷史對話
- `GET /api/quick-actions` → 載入快捷按鈕清單，渲染 `<x-chat.quick-actions>`

**互動細節：**
- Enter 送出，Shift+Enter 換行
- 送出後自動捲動到底部
- AI 回應中顯示 `<x-chat.typing>`
- 回應根據 type 渲染不同元件：
  - `numeric` → `<x-chat.result-number>`
  - `table` → `<x-chat.result-table>`
  - `summary` → 純文字氣泡
  - `clarify` → 氣泡 + 選項按鈕
- 中信心回應顯示 `<x-chat.confidence>` + SQL 展開按鈕
- 側邊欄顯示「新對話」按鈕和歷史對話清單
- Header 右上角使用者 dropdown 包含「登出」按鈕，點擊後呼叫 `POST /api/logout` 撤銷 server token，再清除 localStorage token 並跳轉登入頁

### 3. 查詢日誌頁（admin）

**路由：** `GET /admin/query-logs`
**Web Controller：** `Web\AdminController@queryLogs`
**用途：** 管理員查看所有查詢紀錄

**頁面結構：**
```
<x-layout.page title="查詢日誌">
    ├── 篩選列
    │   ├── 日期範圍選擇
    │   ├── 使用者篩選（下拉）
    │   └── 篩選按鈕
    ├── <x-data.table>             // 查詢紀錄表格
    │   ├── 欄位：時間、使用者、問題、回應、信心度、正確性
    │   └── 操作：標記正確/錯誤
    └── <x-data.pagination>
</x-layout.page>
```

**串接 API：**
- `GET /api/admin/query-logs?page=1&user_id=xxx&date_from=xxx&date_to=xxx`
- `PATCH /api/admin/query-logs/{id}` → 標記正確/錯誤

### 4. 忘記密碼頁

**路由：** `GET /forgot-password`
**Web Controller：** `Web\AuthPageController@forgotPassword`
**用途：** 忘記密碼，寄送重設連結

**頁面結構：**
- Email 輸入框
- 送出按鈕
- 成功提示「重設連結已寄出，請檢查 email」

**串接 API：**
- `POST /api/forgot-password` → 寄送重設 email

### 5. 重設密碼頁

**路由：** `GET /reset-password/{token}`
**Web Controller：** `Web\AuthPageController@resetPassword`

**頁面結構：**
- 新密碼輸入框
- 確認密碼輸入框
- 送出按鈕

**串接 API：**
- `POST /api/reset-password` → 重設密碼

### 6. 快捷按鈕管理頁（admin）

**路由：** `GET /admin/quick-actions`
**Web Controller：** `Web\AdminController@quickActions`
**用途：** 管理員設定聊天頁顯示的快捷按鈕

**頁面結構：**
```
<x-layout.page title="快捷按鈕管理">
    ├── <x-data.table>
    │   ├── 欄位：標籤、查詢內容、排序、啟用狀態
    │   └── 操作：編輯、刪除
    ├── <x-ui.button> 新增快捷按鈕 → <x-ui.modal>
</x-layout.page>
```

**串接 API：**
- `GET /api/admin/quick-actions`
- `POST /api/admin/quick-actions`
- `DELETE /api/admin/quick-actions/{id}`

### 7. 敏感欄位管理頁（admin）

**路由：** `GET /admin/schema-fields`
**Web Controller：** `Web\AdminController@schemaFields`
**用途：** 管理員標記敏感欄位（restricted）

**頁面結構：**
```
<x-layout.page title="欄位權限管理">
    ├── Table 選擇下拉
    ├── <x-data.table>
    │   ├── 欄位：欄位名稱、中文名稱、型別、狀態（正常/受限）
    │   └── 操作：切換受限狀態（toggle）
</x-layout.page>
```

**串接 API：**
- `GET /api/admin/schema-fields?table=customers`
- `PATCH /api/admin/schema-fields/{table}/{column}` → 切換 restricted 狀態

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
| 試用期即將到期 | 頂部顯示 `<x-ui.alert type="warning">` 「免費試用將於 N 天後到期」+ 升級按鈕 |
