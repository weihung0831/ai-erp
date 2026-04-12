# AI ERP

以 AI 驅動的 ERP 平台，用聊天介面取代傳統 ERP 的所有 UI（表單、選單、報表）。客戶透過自然語言對話完成所有 ERP 操作。

[English](README.md)

## 專案狀態

**查詢 + Dashboard 已完成，寫入功能待開發。** Laravel 13 + Sanctum 已 scaffold、48 個 Blade Component、18 支 API（含 SSE 串流 + Dashboard）、Golden Test 150 筆 100% pass、Dashboard 獨立頁面（月/季/年指標 + 趨勢比較）、聊天頁 Claude 風格空狀態 + markdown 渲染。

已建立：
- `Controllers/Api/` — Auth、Chat、StreamChat（SSE）、ChatHistory、Dashboard
- `Controllers/Web/` — AuthPage、ChatPage、DashboardPage
- `Services/` — QueryEngine、OpenAiGateway、SqlValidator、ConfidenceEstimator、TenantManager、DashboardService
- `Models/` — ChatHistory、Conversation、SchemaFieldRestriction、Tenant、User
- `Repositories/` — Contract + Eloquent 實作（Repository Pattern）
- `Enums/` — ChatResponseType、ConfidenceLevel、UserRole、ValueFormat、AggregationType
- DB-per-tenant 含 16 個 tenant migrations + demo seeder
- Dashboard：預定義查詢（銷售/財務/營運），月/季/年時間維度 + 趨勢 %
- 聊天頁：Claude 風格居中歡迎、markdown 渲染（marked.js）、inline Alpine.js UI
- Golden accuracy test suite（150 筆，100% pass）

下一步：Chat-to-operate（INSERT/UPDATE/DELETE + 確認流程 + 檔案上傳）。

## 這是什麼？

一間 ERP 開發公司的產品：不再為每個客戶從零寫表單和 UI，團隊設計好資料庫 schema 後部署通用的 AI 聊天介面。客戶用聊天完成所有操作 — 查詢資料、新增記錄、修改資訊、刪除項目。大量資料用檔案上傳（Excel/CSV）。Dashboard 提供業務概覽。

## 產品組成

| 元件 | 功能 | 取代什麼 |
|------|------|----------|
| **AI 聊天介面** | 自然語言完成所有 CRUD 操作 | 傳統 ERP 的表單、選單、列表 |
| **Dashboard** | 業務關鍵指標概覽 | 傳統 ERP 的報表頁面 |
| **檔案上傳** | 批量資料匯入（Excel/CSV） | 手動逐筆輸入 |
| **Schema 服務** | 團隊手動為客戶設計資料庫 | 不變，這是團隊核心專業 |

所有寫入操作需使用者確認後才執行，不允許靜默寫入。

## 技術棧

| 層級 | 技術 |
|------|------|
| Backend | PHP + Laravel（API-first，回傳 JSON） |
| Database | MySQL（DB-per-tenant 多租戶隔離） |
| AI | Apertis（OpenAI-compatible）+ gpt-4.1-mini + function calling |
| Frontend | Blade + Alpine.js + Axios |
| UI 設計 | [Spotify DESIGN.md](DESIGN.md)（深色唯一模式） |
| Auth | Laravel Sanctum（API token） |
| Cache | Redis（需支援 tag，用於 LLM 回應快取） |

## 架構

- **API-first** — `Controllers/Api/` 回傳 JSON；`Controllers/Web/` 回傳 Blade view，透過 Axios 呼叫 `/api/*`
- **DB-per-tenant** — 每個客戶獨立 MySQL DB
- **頁面路由** — `/` → `/dashboard`（首頁）、`/chat`（聊天）、`/login`、`/forgot-password`、`/reset-password/{token}`
- **Dashboard** — 獨立頁面，使用 Laravel Query Builder 預定義查詢（非 LLM 生成），期間選擇器（月/季/年），趨勢比較 vs 上期
- **聊天頁** — 雙狀態：Claude 風格居中歡迎（空狀態）→ 對話模式（有訊息）；AI 回覆用 markdown 渲染（marked.js）
- **Blade + inline 渲染** — 48 個 Blade Component 存在，但聊天和 Dashboard 頁面因 Alpine.js 動態需求，以 inline CSS class 渲染
- **信心度分層（查詢）** — 高（> 95%）直接回答、中（70-95%）附提示、低（< 70%）不回答改引導釐清
- **寫入一律確認** — 所有 INSERT/UPDATE/DELETE 顯示操作摘要，使用者確認後才執行

## 文件

- [設計文件](docs/design/ai-erp-platform.md) — 產品設計（已核准）
- [系統架構](docs/architecture/system-architecture.md) — 模組、資料庫、API 設計
- [設計模式](docs/design/design-pattern.md) — Repository、Service、Factory、DTO 等
- [UI 設計規範](docs/design/ui-design-spec.md) — 元件視覺規範（Spotify 深色唯一模式）、動畫
- [元件庫](docs/spec/00-component-library.md) — Blade Component 定義
- [後端規格](docs/spec/01-phase1-backend.md) — Chat-to-Operate API（查詢 + 寫入 + 上傳 + Dashboard）
- [前端規格](docs/spec/02-phase1-frontend.md) — Dashboard 獨立頁面 + 聊天頁（Claude 風格 + markdown）+ 寫入確認 + 檔案上傳

## 快速開始

### 環境需求

- PHP **^8.3** + Composer
- Node.js 20+（Vite / Tailwind v4 需要）
- MySQL 8+
- Redis（需支援 tag，用於 LLM 回應快取）
- OpenAI-compatible API key（預設：Apertis + gpt-4.1-mini）

### 安裝

```bash
git clone https://github.com/weihung0831/ai-erp.git
cd ai-erp
git submodule update --init --recursive

composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=DemoSeeder                # 帳號密碼：admin@example.com / admin@example.com
php artisan tenant:provision 1 --fresh --seed          # 佈建租戶 DB + demo 資料
npm run build
```

### 啟動

```bash
# 一鍵起全部：server + queue + logs + Vite watcher
composer run dev

# 或只啟 PHP dev server
php artisan serve
```

### 常用指令

```bash
composer run test                # PHPUnit 測試
./vendor/bin/pint --test         # lint 檢查
./vendor/bin/pint                # lint 自動修
npm run build                    # 前端 production build
npm run dev                      # Vite watch mode
php artisan view:clear           # 清 Blade 編譯快取
php artisan golden:run           # LLM 準確度測試（150 筆，打真實 API）
php artisan golden:run --limit=10  # 快速煙霧測試
php artisan tenant:provision 1 --fresh --seed  # 重新佈建租戶 DB
```

### 開始寫程式前先讀

依序閱讀：

1. [設計文件](docs/design/ai-erp-platform.md) — 做什麼、為什麼做
2. [系統架構](docs/architecture/system-architecture.md) — 模組、資料庫、API
3. [設計模式](docs/design/design-pattern.md) — **開始寫程式前必讀**
4. [UI 設計規範](docs/design/ui-design-spec.md) — 元件視覺規範（Spotify 深色唯一模式）、動畫
5. [元件庫規格](docs/spec/00-component-library.md) — 作為使用元件的參考

## 授權

本專案包含 [awesome-design-md](https://github.com/VoltAgent/awesome-design-md) 作為 submodule，採用 [MIT 授權](awesome-design-md/LICENSE)。
