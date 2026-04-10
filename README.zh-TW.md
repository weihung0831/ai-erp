# AI ERP

以 AI 驅動的 ERP 平台，讓客戶用對話建構和查詢 ERP 系統。

[English](README.md)

## 專案狀態

**Design phase。** Laravel 專案尚未 scaffold，目前 repo 包含設計文件、架構、規格書、UI design system 與已解決問題知識庫。實作依照 [CLAUDE.md](CLAUDE.md) 列出的開發順序進行：從 [00 元件庫](docs/spec/00-component-library.md) 開始，接著 [01 Phase 1 後端](docs/spec/01-phase1-backend.md)、[02 Phase 1 前端](docs/spec/02-phase1-frontend.md)，依序做到 Phase 3。

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
| AI | OpenAI GPT-4o + function calling |
| Frontend | Blade + Alpine.js + Axios |
| UI 設計 | [Claude DESIGN.md](DESIGN.md) |
| Auth | Laravel Sanctum（API token） |
| Cache | Redis（需支援 tag，用於 LLM 回應快取） |

## 架構

- **API-first** — `Controllers/Api/` 回傳 JSON，`Controllers/Web/` 回傳 Blade view，透過 Axios 呼叫 `/api/*`
- **DB-per-tenant** — 每個客戶獨立 MySQL DB
- **Blade 元件化** — 所有 UI 用巢狀命名空間元件（`<x-chat.bubble>`、`<x-data.table>` 等）

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
- [元件庫](docs/spec/00-component-library.md) — 42 個 Blade Component 定義
- [已解決問題知識庫](docs/solutions/) — 過往 bug、best practice、workflow pattern，按 category 組織，frontmatter 含 `module`、`tags`、`problem_type`

## 快速開始

### 先讀設計文件

Laravel 專案尚未 scaffold，目前請從已核准的設計文件開始讀：

```bash
git clone https://github.com/weihung0831/ai-erp.git
cd ai-erp
git submodule update --init --recursive
```

依序閱讀：

1. [設計文件](docs/design/ai-erp-platform.md) — 做什麼、為什麼做
2. [系統架構](docs/architecture/system-architecture.md) — 模組、資料庫、API
3. [設計模式](docs/design/design-pattern.md) — **開始寫程式前必讀**
4. [UI 設計規範](docs/design/ui-design-spec.md) — 元件視覺規範、dark mode、動畫（元件庫的依據）
5. [元件庫規格](docs/spec/00-component-library.md) — 第一個實作目標，之後依照 [CLAUDE.md](CLAUDE.md) 所列的 spec 開發順序進行

### 預計技術棧（實作開始時需要）

- PHP >= 8.2 + Composer
- MySQL >= 8.0
- Redis（需支援 tag）
- Node.js（前端資源）
- OpenAI API key（GPT-4o）

## 授權

本專案包含 [awesome-design-md](https://github.com/VoltAgent/awesome-design-md) 作為 submodule，採用 [MIT 授權](awesome-design-md/LICENSE)。
