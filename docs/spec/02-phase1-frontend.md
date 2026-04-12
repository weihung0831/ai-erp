# 前端規格書：AI ERP — Chat-to-Operate 介面

日期：2026-04-12（更新）
狀態：已核准
依據：[後端規格](01-phase1-backend.md) / [元件庫](00-component-library.md) / [UI 設計規範](../design/ui-design-spec.md)

## 進度追蹤

### 頁面
- [x] 登入頁
- [x] 忘記密碼頁
- [x] 重設密碼頁
- [x] 聊天頁（查詢功能）
- [ ] 聊天頁（寫入確認 UI）
- [ ] 聊天頁（檔案上傳 UI）
- [ ] 聊天頁（Dashboard 區塊）

### 元件（使用中）
- [x] `<x-layout.page>` / `<x-layout.sidebar>` / `<x-layout.header>`
- [x] `<x-chat.bubble>` / `<x-chat.input>` / `<x-chat.confidence>` / `<x-chat.typing>` / `<x-chat.result-table>` / `<x-chat.result-number>`
- [x] `<x-form.input>`
- [x] `<x-ui.button>` / `<x-ui.alert>` / `<x-ui.dropdown>` / `<x-ui.loading>`

### 待新增元件
- [ ] `<x-chat.confirm-action>` — 寫入確認卡片
- [ ] `<x-chat.upload-preview>` — 檔案上傳預覽
- [ ] `<x-chat.file-input>` — 檔案上傳按鈕
- [ ] `<x-dashboard.stat-card>` — Dashboard 指標卡片
- [ ] `<x-dashboard.panel>` — Dashboard 面板容器

### Alpine.js Store
- [x] `chatStore`
- [x] `authStore`

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
**用途：** AI ERP 主介面 — 查詢、操作、Dashboard

**頁面結構：**
```
<x-layout.page title="AI ERP">
    ├── <x-layout.sidebar>              // 左側導覽（對話歷史）
    ├── 主內容區
    │   ├── <x-dashboard.panel>         // Dashboard 指標區（可收合）
    │   │   └── <x-dashboard.stat-card> // 動態指標卡片 x N
    │   ├── 對話歷史區域                  // 捲動區域
    │   │   ├── 歡迎訊息                 // AI 初始引導
    │   │   ├── <x-chat.bubble>         // 查詢回應
    │   │   ├── <x-chat.confirm-action> // 寫入確認卡片
    │   │   ├── <x-chat.upload-preview> // 上傳預覽
    │   │   └── <x-chat.typing>         // AI 回應中
    │   └── <x-chat.input>             // 輸入框 + 檔案上傳按鈕
</x-layout.page>
```

**Alpine.js Store（chatStore 擴展）：**
- `messages[]` — 對話歷史
- `input` — 目前輸入
- `loading` — 是否等待 AI 回應
- `conversationId` — 目前對話 ID
- `dashboardStats[]` — Dashboard 指標資料
- `send()` — 送出訊息，呼叫 `POST /api/chat`
- `confirmMutation(mutationId)` — 確認寫入，呼叫 `POST /api/chat/confirm/{id}`
- `rejectMutation(mutationId)` — 拒絕寫入，呼叫 `POST /api/chat/reject/{id}`
- `uploadFile(file)` — 上傳檔案，呼叫 `POST /api/upload/preview`
- `confirmUpload(uploadId)` — 確認匯入，呼叫 `POST /api/upload/confirm/{id}`
- `rejectUpload(uploadId)` — 取消匯入，呼叫 `POST /api/upload/reject/{id}`
- `loadDashboard()` — 載入 Dashboard，呼叫 `GET /api/dashboard`
- `newConversation()` — 開新對話

**串接 API：**
- `POST /api/chat` → 送出訊息，取得 AI 回應
- `POST /api/chat/confirm/{mutation_id}` → 確認寫入操作
- `POST /api/chat/reject/{mutation_id}` → 拒絕寫入操作
- `POST /api/upload/preview` → 上傳檔案取得預覽
- `POST /api/upload/confirm/{upload_id}` → 確認匯入
- `POST /api/upload/reject/{upload_id}` → 取消匯入
- `GET /api/dashboard` → 載入 Dashboard 指標
- `GET /api/chat/history` → 載入對話清單（sidebar）
- `GET /api/chat/history/{uuid}` → 載入單一對話的所有 messages
- `DELETE /api/chat/history/{uuid}` → 刪除對話

**互動細節：**
- Enter 送出，Shift+Enter 換行
- 送出後自動捲動到底部
- AI 回應中顯示 `<x-chat.typing>`
- 回應根據 type 渲染不同元件：
  - `numeric` → `<x-chat.result-number>`
  - `table` → `<x-chat.result-table>`
  - `summary` → 純文字氣泡
  - `clarify` → 氣泡 + 選項按鈕
  - `pending_confirmation` → `<x-chat.confirm-action>`（操作摘要 + 確認/取消按鈕）
  - `mutation_result` → 氣泡（成功：綠色邊框 / 失敗：紅色邊框 + 錯誤原因）
  - `upload_preview` → `<x-chat.upload-preview>`（預覽表格 + 欄位對應 + 確認/取消）
- 中信心回應顯示 `<x-chat.confidence>` + SQL 展開按鈕
- 側邊欄可收合/展開（`«`/`»` 按鈕），狀態存 localStorage
- 側邊欄顯示「新對話」按鈕和歷史對話清單
- 對話項目 hover 時顯示 `×` 刪除按鈕
- Header 右上角使用者 dropdown 包含「登出」按鈕
- Dashboard 面板預設展開，可收合，狀態存 localStorage
- 檔案上傳：輸入框旁的 📎 按鈕，或拖放檔案到聊天區域

### 3. 忘記密碼頁

**路由：** `GET /forgot-password`
**Web Controller：** `Web\AuthPageController@forgotPassword`
**用途：** 忘記密碼，寄送重設連結

**頁面結構：**
- Email 輸入框
- 送出按鈕
- 成功提示「重設連結已寄出，請檢查 email」

**串接 API：**
- `POST /api/forgot-password` → 寄送重設 email

### 4. 重設密碼頁

**路由：** `GET /reset-password/{token}`
**Web Controller：** `Web\AuthPageController@resetPassword`

**頁面結構：**
- 新密碼輸入框
- 確認密碼輸入框
- 送出按鈕

**串接 API：**
- `POST /api/reset-password` → 重設密碼

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
