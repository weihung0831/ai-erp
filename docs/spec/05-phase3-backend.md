# 功能規格書：Phase 3 — SaaS 轉型與知識回饋循環

日期：2026-04-11
狀態：已核准
依據：[設計文件](../design/ai-erp-platform.md) / [架構文件](../architecture/system-architecture.md)

## 進度追蹤

### 知識回饋
- [ ] US-1：Schema 回饋到知識庫
- [ ] US-2：AI 品質持續提升
- [ ] US-3：跨客戶 pattern 分析（延後至租戶數 > 30）

### 自助註冊與 Onboarding
- [ ] US-4：客戶自助註冊（含 email 驗證、reCAPTCHA）
- [ ] US-5：引導式 Onboarding

### 訂閱與付費
- [ ] US-6：訂閱方案
- [ ] US-7：付費（金流串接 + 電子發票）
- [ ] US-8：用量儀表板

### 平台管理
- [ ] US-9：營運儀表板
- [ ] US-10：租戶管理（含資料匯出）

### 基礎建設
- [ ] 金流服務選定（綠界 or 藍新）
- [ ] 電子發票加值中心串接
- [ ] 租戶狀態機（active → grace → suspended → archived → deleted）
- [ ] 稅務計算邏輯

## 目標

讓系統越用越聰明：每個新客戶的 schema 回饋到 domain knowledge，提升 AI 建構和查詢的品質。同時完成從接案公司到 SaaS 公司的轉型：客戶可以自助註冊、自行建系統、自行付費。

## 範圍

### 包含

- Domain knowledge 回饋機制（客戶 schema 經審核後回饋到知識庫）
- 自助註冊與 onboarding 流程
- 訂閱制付費（方案管理、用量計費）
- 多租戶自動化部署（新租戶 DB 自動建立）
- 管理後台（平台營運儀表板）
- 台灣在地金流串接
- 稅務計算邏輯（Phase 2 延後的部分）

### 不包含

- 行動裝置 App
- 多語言支援（僅繁體中文）
- 第三方 ERP 資料匯入/匯出
- 進階報表設計器（圖表、PDF）

## 使用者故事

### 知識回饋

#### US-1：Schema 回饋到知識庫

> 身為平台管理員，我可以審核客戶建立的 schema，將優質的模式回饋到 domain knowledge，讓未來的客戶受益。

**驗收條件：**
- 管理後台列出所有租戶的 schema 變更紀錄
- 管理員可標記某個 schema 為「優質」，加入候選清單
- 管理員審核後，schema pattern 寫入對應產業的 domain knowledge JSON
- 回饋後，新客戶在同產業建模組時 AI 會參考這些 pattern

#### US-2：AI 品質持續提升

> 身為客戶，我期待系統每次更新後，AI 的建構和查詢品質都會變好。

**驗收條件：**
- 定期跑 golden test suite（Phase 1）和 build test suite（Phase 2），記錄分數趨勢
- 管理後台顯示準確率趨勢圖（按週/月）
- 準確率下降時自動 alert 管理員

#### US-3：跨客戶 pattern 分析（延後至租戶數 > 30 時實作）

> 身為平台管理員，我想看到哪些欄位、模組被最多客戶使用，幫助我優化 domain knowledge。

**驗收條件：**
- 管理後台統計：最常見的 table 名稱、最常見的欄位、最常見的產業
- 識別缺失 pattern：客戶手動新增但 domain knowledge 沒有的欄位/模組

注意：此功能在初期租戶量少時價值有限，延後至租戶數超過 30 時再啟動開發。

### 自助註冊與 Onboarding

#### US-4：客戶自助註冊

> 身為新客戶，我可以在網站上自行註冊，不需要聯繫業務人員。

**驗收條件：**
- 註冊頁面：公司名稱、聯絡人、email、密碼
- Email 驗證（發送驗證信，點擊連結後啟用帳號）
- reCAPTCHA 防機器人
- 註冊後自動建立租戶 DB
- 進入 onboarding 流程（產業選擇 → 模組建議 → AI 建構）
- 免費試用期（天數可設定，預設 14 天）

> 注意：忘記密碼 / 重設密碼流程已在 Phase 1 實作，Phase 3 直接複用。

#### US-5：引導式 Onboarding

> 身為新客戶，註冊後 AI 引導我一步步建好第一個模組，不會感到迷失。

**驗收條件：**
- 步驟 1：AI 詢問產業別
- 步驟 2：AI 根據產業推薦模組（可勾選）
- 步驟 3：AI 建構選定的模組（Chat-to-build 流程）
- 步驟 4：引導客戶輸入第一筆資料
- 步驟 5：示範 Chat-to-query（「試試問我：這個月營收多少？」）
- 全程可跳過任一步驟

### 訂閱與付費

#### US-6：訂閱方案

> 身為客戶，我可以選擇適合的付費方案。

**驗收條件：**
- 方案頁面顯示各方案的功能和價格
- 方案差異：查詢次數上限、模組數量上限、使用者人數上限
- 可隨時升降級
- 試用期到期前 3 天提醒

#### US-7：付費

> 身為台灣客戶，我可以用常見的付費方式付款。

**驗收條件：**
- 支援信用卡付款（串接綠界 ECPay 或藍新 NewebPay）
- 自動每月扣款
- 發票自動開立（電子發票）
- 付款失敗時寬限 7 天，期間功能正常；逾期後降為唯讀模式

#### US-8：用量儀表板

> 身為客戶，我想知道這個月用了多少查詢額度。

**驗收條件：**
- 客戶後台顯示：本月查詢次數、剩餘額度、token 用量
- 額度用到 80% 時提醒
- 額度用完時提示升級方案

### 平台管理

#### US-9：營運儀表板

> 身為平台管理員，我要看到整體營運狀況。

**驗收條件：**
- 總覽：租戶數、活躍用戶數、本月營收
- 租戶列表：方案、用量、建立日期、最後活動時間
- AI 使用統計：總查詢次數、總 token 用量、LLM 成本
- 準確率追蹤：Phase 1 / Phase 2 的 test suite 分數趨勢

#### US-10：租戶管理

> 身為平台管理員，我可以管理租戶的狀態和方案。

**驗收條件：**
- 查看租戶詳情（schema、用量、帳單）
- 手動調整方案或額度
- 停用/啟用租戶
- 匯出租戶資料（個資法要求）：匯出格式為 SQL dump + CSV（每表一個檔案），包含所有業務資料和 schema metadata。僅 admin 角色或平台管理員可觸發，匯出檔案 24 小時後自動刪除

## Domain Knowledge 回饋機制

### 回饋流程

```
1. 客戶透過 Chat-to-build 建立模組
2. Schema 變更記錄到 schema_versions
3. 平台管理員在後台瀏覽各租戶的 schema
4. 管理員標記優質 pattern → 進入候選清單
5. 管理員審核候選 pattern：
   ├── 去除客戶專屬資訊（公司名稱、特殊命名）
   ├── 抽象化為通用 pattern
   └── 歸類到對應產業
6. 使用標準化範本抽象化：
   ├── 移除客戶專屬命名（公司名稱 → 通用名）
   ├── 欄位名稱統一為 snake_case
   └── 標記必填/選填、型別、預設值
7. 寫入 domain-knowledge/industries/{industry}.json
8. 跑 build test suite 確認新 pattern 不影響既有準確率
```

### 回饋品質控制

- 至少 3 個不同租戶使用過類似 pattern 才納入考慮
- 每次回饋後跑完整 test suite 回歸
- 回饋內容不包含任何客戶的業務資料，僅保留 schema 結構

## 自動化部署

### 新租戶建立流程

```
1. 客戶完成註冊
2. 系統在主 DB 建立 tenant 記錄
3. 自動建立 tenant_{id}_db（MySQL CREATE DATABASE）
4. 建立 read-only MySQL user（供 Query Engine 使用）
5. 建立 read-write MySQL user（供 Build Engine 使用）
6. 初始化 schema_metadata 表
7. 進入 onboarding 流程
```

### 租戶停用流程

```
1. 付款失敗超過寬限期 / 管理員手動停用
2. 租戶狀態改為 suspended
3. 使用者登入後只能查看資料（唯讀），不能新增/修改/建構
4. 停用 30 天後進入封存狀態（可匯出資料，不可登入）
5. 封存 90 天後刪除租戶 DB（刪除前 7 天 email 通知）

**租戶狀態機：**
- active → grace（扣款失敗，寬限 7 天）
- grace → active（補繳成功）
- grace → suspended（寬限期過，唯讀模式）
- suspended → active（補繳成功）
- suspended → archived（停用 30 天，可匯出資料）
- archived → deleted（封存 90 天，刪除 DB）
- 任何狀態 → active（管理員手動恢復）

排程 job：每日凌晨檢查各租戶狀態，自動觸發狀態轉換和 email 通知
```

## 訂閱方案設計

| 項目 | 免費試用 | 基礎版 | 專業版 | 企業版 |
|------|----------|--------|--------|--------|
| 期限 | 14 天 | 月繳 | 月繳 | 年繳 |
| 查詢次數/月 | 100 | 500 | 2,000 | 無限 |
| 模組數量 | 5 | 10 | 30 | 100 |
| 使用者人數 | 2 | 5 | 15 | 無限 |
| AI 建構次數/月 | 3 | 10 | 50 | 無限 |
| 版本歷史 | 7 天 | 30 天 | 90 天 | 永久 |
| 支援 | 社群 | email | 優先 email | 專人 |
| 價格 | 免費 | 待定 | 待定 | 待定 |

注意：
- 價格需根據 LLM 成本和市場調研決定，此處不預設
- 版本歷史保留期限依方案差異化，覆寫 Phase 2 的「永久保留」設定。降級時：超出新方案保留期的歷史記錄保留 30 天供匯出，之後刪除

## 台灣在地化

### 金流串接

- 綠界 ECPay 或藍新 NewebPay（Phase 3 開始前依以下標準決定：API 文件品質、Laravel SDK 成熟度、定期定額支援度、手續費率）
- 支援信用卡定期定額扣款
- 支援電子發票自動開立（透過加值中心串接財政部電子發票平台）
  - 開立時機：付款完成後即時開立
  - 支援作廢和折讓流程
  - 客戶後台可查詢和下載發票
  - 加值中心選擇在 Phase 3 開始前決定（綠界電子發票 / 鯨躍 ezPay 等）

### 稅務計算邏輯（Phase 2 延後至此）

- 營業稅 5% 自動計算（可設定內含/外加）
- 統一發票格式欄位自動加入（買受人統編、品名、數量、單價、稅額）
- 發票號碼管理（字軌配號、自動跳號）

## 非功能需求

| 項目 | 要求 |
|------|------|
| 註冊到可用 | < 2 分鐘（含 DB 建立和 onboarding 開始） |
| 租戶數量 | 初期支援 100 租戶（單一 MySQL server），超過後需評估分庫策略（按租戶 ID hash 分配到多台 DB server） |
| 金流回應 | < 10 秒（含第三方 API） |
| 資料保留 | 符合個資法：客戶可申請匯出/刪除 |
| SLA | 99.5% uptime |

## 錯誤處理

| 場景 | 行為 |
|------|------|
| 租戶 DB 建立失敗 | 重試 2 次（依設計模式規範），仍失敗則通知管理員，使用者看到「註冊處理中，我們會盡快通知您」 |
| 金流扣款失敗 | 重試 3 次（隔日），發 email 通知客戶更新付款方式 |
| Domain knowledge 回饋後準確率下降 | 自動 rollback 該次回饋，通知管理員 |
| 租戶數量接近上限 | 80% 時 alert 管理員評估擴容 |

## API Endpoint 清單

### 公開（無需登入）

| Method | Path | 說明 |
|--------|------|------|
| `POST` | `/api/register` | 註冊新帳號 |
| `GET` | `/api/verify-email/{token}` | 驗證 email |
| `POST` | `/api/verify-email/resend` | 重新寄送驗證信 |

### Onboarding

| Method | Path | 說明 |
|--------|------|------|
| `POST` | `/api/onboarding/industry` | 儲存產業選擇 |
| `POST` | `/api/onboarding/complete` | 標記 onboarding 完成 |

### 客戶後台

| Method | Path | 說明 |
|--------|------|------|
| `GET` | `/api/account/usage` | 取得用量資料 |
| `GET` | `/api/account/subscription` | 取得目前訂閱 |
| `PUT` | `/api/account/subscription` | 升降級方案 |
| `PUT` | `/api/account/payment-method` | 更換付款方式 |
| `GET` | `/api/account/invoices` | 發票列表 |
| `GET` | `/api/account/invoices/{id}/download` | 下載發票 PDF |

### 平台管理

| Method | Path | 說明 |
|--------|------|------|
| `GET` | `/api/admin/dashboard` | 營運儀表板聚合資料 |
| `GET` | `/api/admin/tenants` | 租戶列表 |
| `PATCH` | `/api/admin/tenants/{id}` | 停用/啟用租戶 |
| `POST` | `/api/admin/tenants/{id}/export` | 匯出租戶資料 |
| `GET` | `/api/admin/knowledge/candidates` | 回饋候選清單 |
| `POST` | `/api/admin/knowledge/candidates/{id}/approve` | 審核通過 |
| `POST` | `/api/admin/knowledge/candidates/{id}/reject` | 審核拒絕 |
| `GET` | `/api/admin/knowledge/industries/{industry}` | 產業知識瀏覽 |
| `GET` | `/api/admin/accuracy` | 準確率趨勢 |
| `GET` | `/api/admin/accuracy/errors` | 錯誤查詢列表 |
