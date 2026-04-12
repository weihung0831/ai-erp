# AI ERP

以 AI 驅動的 ERP 平台，讓客戶用對話建構和查詢 ERP 系統。

[English](README.md)

## 專案狀態

**Phase 1 後端完成，前端整合中。** Laravel 13 + Sanctum 已 scaffold、42 個 Blade Component 已全數實作、Chat-to-query 後端 pipeline（LLM → SQL 生成 → 執行 → 回傳）已端到端跑通。

已建立：
- `Controllers/Api/` — Auth、Chat、StreamChat（SSE）、ChatHistory、QuickAction、Admin（QueryLog、SchemaField）
- `Services/` — QueryEngine、OpenAiGateway、SqlValidator、ConfidenceEstimator、TenantManager、TenantDatabaseManager
- `Models/` — ChatHistory、Conversation、QueryLog、QuickAction、SchemaFieldRestriction、Tenant、User
- `Repositories/` — Contract + Eloquent 實作（Repository Pattern）
- DB-per-tenant 含 15 個 tenant migrations + demo seeder
- Event-driven query logging、Golden accuracy test suite（150 筆案例）

**尚未建立：** Phase 1 聊天 UI 頁面、Phase 2 Build Engine、Phase 3 SaaS 模組。

## 這是什麼？

一間 ERP 開發公司的產品：客戶不再需要請開發團隊從零建 ERP，而是用自然語言描述需求，AI 自動產生資料庫 schema、API 和 UI。系統建好後，使用者用聊天查資料，取代複雜的 ERP 介面操作。

## 核心模組

| 模組 | 面向 | 做什麼 |
|------|------|--------|
| **Chat-to-build** | 客戶 | 用對話描述需求，AI 產生 schema + UI |
| **Chat-to-query** | 同一客戶 | 用對話查資料，取代複雜 ERP 操作 |

## 技術棧

| 層級 | 技術 |
|------|------|
| Backend | PHP + Laravel（API-first，回傳 JSON） |
| Database | MySQL（DB-per-tenant 多租戶隔離） |
| AI | Apertis（OpenAI-compatible）+ gpt-4.1-mini + function calling |
| Frontend | Blade + Alpine.js + Axios |
| UI 設計 | [Claude DESIGN.md](DESIGN.md) |
| Auth | Laravel Sanctum（API token） |
| Cache | Redis（需支援 tag，用於 LLM 回應快取） |

## 架構

- **API-first** — `Controllers/Api/` 回傳 JSON；`Controllers/Web/` 回傳 Blade view，透過 Axios 呼叫 `/api/*`
- **DB-per-tenant** — 每個客戶獨立 MySQL DB
- **Blade 元件化** — 所有 UI 用巢狀命名空間元件（`<x-chat.bubble>`、`<x-data.table>` 等）。42 個元件庫已全部完成，可在 `/components` 預覽

## 開發階段

| 階段 | 範圍 | 規格 |
|------|------|------|
| 1 | Chat-to-query：自然語言轉 SQL | [後端](docs/spec/01-phase1-backend.md) / [前端](docs/spec/02-phase1-frontend.md) |
| 2 | Chat-to-build：對話建構 schema + CRUD UI | [後端](docs/spec/03-phase2-backend.md) / [前端](docs/spec/04-phase2-frontend.md) |
| 3 | SaaS：自助註冊、付費、知識回饋循環 | [後端](docs/spec/05-phase3-backend.md) / [前端](docs/spec/06-phase3-frontend.md) |

## 文件

- [設計文件](docs/design/ai-erp-platform.md) — 產品設計（已核准）
- [系統架構](docs/architecture/system-architecture.md) — 模組、資料庫、API 設計
- [設計模式](docs/design/design-pattern.md) — Repository、Service、Factory、DTO 等
- [UI 設計規範](docs/design/ui-design-spec.md) — 元件視覺規範、dark mode、動畫
- [元件庫](docs/spec/00-component-library.md) — 42 個 Blade Component 定義（已全部實作）

## 快速開始

### 環境需求

- PHP **^8.3** + Composer
- Node.js 20+（Vite / Tailwind v4 需要）
- MySQL 8+
- Redis（需支援 tag，Phase 1 用於 LLM 回應快取）
- OpenAI-compatible API key（預設：Apertis + gpt-4.1-mini，Phase 1 聊天功能需要）

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
php artisan db:seed --class=DemoSeeder   # 建立示範租戶 + 範例資料
npm run build
```

### 啟動

```bash
# 一鍵起全部：server + queue + logs + Vite watcher
composer run dev

# 或只啟 PHP dev server
php artisan serve
```

打開 **http://localhost:8000/components** 檢視 Blade 元件庫展示頁。

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
```

### 開始寫程式前先讀

依序閱讀：

1. [設計文件](docs/design/ai-erp-platform.md) — 做什麼、為什麼做
2. [系統架構](docs/architecture/system-architecture.md) — 模組、資料庫、API
3. [設計模式](docs/design/design-pattern.md) — **開始寫程式前必讀**
4. [UI 設計規範](docs/design/ui-design-spec.md) — 元件視覺規範、dark mode、動畫
5. [元件庫規格](docs/spec/00-component-library.md) — 已實作完成，作為使用元件的參考

## 授權

本專案包含 [awesome-design-md](https://github.com/VoltAgent/awesome-design-md) 作為 submodule，採用 [MIT 授權](awesome-design-md/LICENSE)。
