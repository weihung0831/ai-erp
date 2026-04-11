# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 語言規範

- 所有回應一律使用**繁體中文**，技術用詞可使用英文

## Git 規則

- **禁止擅自 commit 或 push**，所有 commit 和 push 操作必須經過使用者明確同意後才能執行
- Commit message 必須附上 `Co-Authored-By: Claude Opus 4.6 (1M context) <noreply@anthropic.com>` 作為單行 footer，標示 AI 協作

## 設計文件為唯一真實來源

- `docs/design/ai-erp-platform.md` 已 approved，是本專案的唯一真實來源，不得擅自偏離
- 回答架構 / 流程 / 資料結構問題前，先讀對應的 `docs/spec/*.md` 或 `docs/architecture/system-architecture.md`，不要憑記憶作答
- 若發現 spec 與程式碼衝突，先回報使用者，不要自行「修正」

## 專案概述

AI ERP 平台，讓客戶用對話建構和查詢 ERP 系統。團隊本身是 ERP 開發公司，本產品將人工開發工作產品化。

## 目前狀態

**Phase 1 實作中。** Laravel 13 + Sanctum 已 scaffold、`docs/spec/00-component-library.md` 的 42 個 Blade Component 已全數實作在 `resources/views/components/{namespace}/`，showcase 頁面在 `/components`。

**尚未建立：** `app/Http/Controllers/Api/`、`app/Services/`（Query/Build Engine、TenantManager、LlmGateway）、`app/Models/`（除 Laravel 預設）、`database/migrations/` 的 chat_histories / query_logs / subscriptions。實作新功能前先 `Glob` 確認對應目錄是否存在，不要假設 Service / Repository 已經在。

## 常用指令

```bash
# 一鍵啟動 server + queue + logs + vite
composer run dev

# 只啟 PHP dev server（背景執行或 showcase 檢視時用這個）
php artisan serve

# 前端 asset 打包
npm run build      # production
npm run dev        # watch mode

# 測試
composer run test

# Lint（push 前一定要跑）
./vendor/bin/pint --test        # 檢查
./vendor/bin/pint                # 自動修

# 清快取（改了 Blade component 或 config 後）
php artisan view:clear
php artisan config:clear
```

## 技術棧

| 層級 | 技術 |
|------|------|
| Backend | PHP + Laravel（純 API，回傳 JSON） |
| Database | MySQL（DB-per-tenant 多租戶隔離） |
| AI | OpenAI GPT-4o + function calling |
| Frontend | Blade + Alpine.js + Axios（同一 Laravel 專案內前後端分離） |
| UI 設計 | Claude DESIGN.md（根目錄 `DESIGN.md`） |
| Auth | Laravel Sanctum（API token），OAuth2 未來再導入 |
| Cache | Redis（LLM 回應快取，需支援 tag） |

## 架構要點

- **前後端分離：** `Controllers/Api/`（尚未建立，Phase 1 實作時新增）回傳 JSON 處理業務邏輯，`Controllers/Web/` 只回傳 Blade view，不碰資料庫
- **DB-per-tenant：** 每個客戶獨立 MySQL DB，主資料庫存平台運營資料
- **API-first：** Blade 頁面透過 Axios 呼叫自己的 `/api/*` 端點
- **Blade 元件化：** 所有 UI 用 `<x-chat.bubble>` 等巢狀命名空間的 Blade Component
- **信心度分層：** Chat-to-query 的核心機制——高（> 95%）直接回答、中（70-95%）附提示、低（< 70%）不回答改引導釐清

## 設計模式

開發前必讀 [docs/design/design-pattern.md](docs/design/design-pattern.md)，關鍵規則：
- Thin Controller：Controller 只接 request → 呼叫 Service → 回傳 response
- Repository Pattern：資料存取封裝在 Repository，不直接操作 Model
- Factory 統一用 Service Provider 綁定，不用 static method
- DTO 和 FormRequest 分離：FormRequest 驗證請求，DTO 傳遞資料
- Web Controller 不呼叫 Service，不操作資料庫

## Blade 元件系統

所有 42 個元件在 `resources/views/components/{namespace}/`，showcase 頁 `/components`。

### 命名空間 gotcha：`layout/` vs `layouts/`（單複數）

- `components/layouts/app.blade.php` — HTML 框架（含 `<html>`、字型、Vite assets）。用法 `<x-layouts.app>...</x-layouts.app>`。
- `components/layout/{sidebar,header,page}.blade.php` — 實際的版面導覽元件。用法 `<x-layout.sidebar>`、`<x-layout.header>`。

兩者差一個 `s`。別混用。

### 設計系統 class（在 `resources/css/app.css`）

新元件一律用 CSS class 和變數，**禁止 inline style**，否則 dark mode 不會跟著切。

- **Heading：** `.h-hero` (40px) / `.h-section` (32px) / `.h-card` (24px) / `.h-sub` (20px)
- **Card：** `.card` / `.card.is-selected`（brand ring 高亮）
- **Stack / row：** `.stack-xs/sm/md/lg/xl`（flex column + gap）、`.row-sm/md`（flex row + gap）
- **Progress：** `.progress-track` + `.progress-fill.is-ok/is-warning/is-danger`
- **Step badge：** `.step-badge.is-active` / `.step-dot.is-done`
- **Confidence：** `.confidence-high/mid/low`、`.stat-trend-up/-down`
- **Alpine：** 需要隱藏的元件加 `x-cloak`（全域規則已定義）

CSS 變數體系：`--bg-page/-card/-white/-sand/-cream`, `--text-primary/-secondary/-tertiary`, `--brand/-hover/-active`, `--border`, `--ring-default/-focus/-brand`。完整清單見 `app.css` 的 `:root` 區塊。

### `displayName` vs `display_name`

非對稱的命名 convention，依元件來源決定：
- **Build 元件**（`<x-build.*>`、`<x-onboarding.*>` 等 UI-constructed 資料）→ **camelCase** `displayName`
- **CRUD 元件**（`<x-crud.dynamic-*>` 吃 `schema_metadata`）→ **snake_case** `display_name`（對應 DB 欄位名）

不要在元件內加 `?? $x['other']` 的 defensive fallback，保留單一 source 就好。

## 文件索引

### 設計
- [設計文件](docs/design/ai-erp-platform.md) — 產品設計（已核准）
- [設計模式](docs/design/design-pattern.md) — 後端 9 種 + 前端 2 種 pattern
- [UI 設計規範](docs/design/ui-design-spec.md) — 元件視覺規範、dark mode、動畫

### 架構
- [系統架構](docs/architecture/system-architecture.md) — 模組、資料庫、API、目錄結構

### 規格書（依開發順序）
- [00 元件庫](docs/spec/00-component-library.md) — 42 個 Blade Component 定義
- [01 Phase 1 後端](docs/spec/01-phase1-backend.md) — Chat-to-query API
- [02 Phase 1 前端](docs/spec/02-phase1-frontend.md) — 聊天介面頁面
- [03 Phase 2 後端](docs/spec/03-phase2-backend.md) — Chat-to-build API
- [04 Phase 2 前端](docs/spec/04-phase2-frontend.md) — 建構介面頁面
- [05 Phase 3 後端](docs/spec/05-phase3-backend.md) — SaaS + 知識回饋
- [06 Phase 3 前端](docs/spec/06-phase3-frontend.md) — 註冊、管理後台

## Git Submodules

- `awesome-design-md/` — [VoltAgent/awesome-design-md](https://github.com/VoltAgent/awesome-design-md) 的 DESIGN.md 集合（參考用，已選定 Claude 風格並下載到根目錄 `DESIGN.md`）

```bash
git submodule update --init --recursive
```

## Skill routing

When the user's request matches an available skill, ALWAYS invoke it using the Skill
tool as your FIRST action. Do NOT answer directly, do NOT use other tools first.
The skill has specialized workflows that produce better results than ad-hoc answers.

Key routing rules:
- Product ideas, "is this worth building", brainstorming → invoke office-hours
- Bugs, errors, "why is this broken", 500 errors → invoke investigate
- Ship, deploy, push, create PR → invoke ship
- QA, test the site, find bugs → invoke qa
- Code review, check my diff → invoke review
- Update docs after shipping → invoke document-release
- Weekly retro → invoke retro
- Design system, brand → invoke design-consultation
- Visual audit, design polish → invoke design-review
- Architecture review → invoke plan-eng-review
- Save progress, checkpoint, resume → invoke checkpoint
- Code quality, health check → invoke health
