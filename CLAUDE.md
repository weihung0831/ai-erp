# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 語言規範

- 所有回應一律使用**繁體中文**，技術用詞可使用英文

## Git 規則

- **禁止擅自 commit 或 push**，所有 commit 和 push 操作必須經過使用者明確同意後才能執行
- Commit message **只寫 title 一行 + 空行 + `Co-Authored-By` footer**，不要寫 body / 條列解釋 / 多段描述。格式如下：

  ```
  chore(deploy): add zbpack.json for Zeabur Laravel deploy

  Co-Authored-By: Claude Opus 4.6 (1M context) <noreply@anthropic.com>
  ```

  為什麼：使用者看 diff 跟 file 本身就知道做了什麼，不需要 commit message 重複解釋。寫 body 反而是噪音。

## 設計文件為唯一真實來源

- `docs/design/ai-erp-platform.md` 已 approved，是本專案的唯一真實來源，不得擅自偏離
- 回答架構 / 流程 / 資料結構問題前，先讀對應的 `docs/spec/*.md` 或 `docs/architecture/system-architecture.md`，不要憑記憶作答
- 若發現 spec 與程式碼衝突，先回報使用者，不要自行「修正」

## 專案概述

AI ERP 平台，讓客戶用對話建構和查詢 ERP 系統。團隊本身是 ERP 開發公司，本產品將人工開發工作產品化。

## 目前狀態

**Phase 1 後端完成、前端整合中。** Laravel 13 + Sanctum 已 scaffold、42 個 Blade Component 已全數實作、Chat-to-query 後端 17 支 API 全部到位（含 SSE 串流）、Golden Test 150 筆 100% pass。

**已建立的核心目錄：**
- `app/Http/Controllers/Api/` — AuthController, ChatController, StreamChatController, ChatHistoryController, QuickActionController, Admin/{QueryLogController, QuickActionController, SchemaFieldController}
- `app/Services/` — Ai/（LlmGateway, OpenAiGateway, QueryEngine, SqlValidator, ConfidenceEstimator）, Schema/, Tenant/（TenantManager, TenantDatabaseManager）
- `app/Models/` — ChatHistory, Conversation, QueryLog, QuickAction, SchemaFieldRestriction, Tenant, User
- `app/Repositories/` — Contracts/ + Eloquent/ 實作（Repository Pattern 已套用）
- `app/DataTransferObjects/` — Chat/, Schema/
- `app/Enums/` — ChatResponseType, ConfidenceLevel, UserRole, ValueFormat
- `app/Events/QueryExecuted` + `app/Listeners/LogQueryListener`（event-driven query logging）
- `app/Support/` — CurrencyFormatter, NumberFormatter
- `app/Console/Commands/` — GoldenAccuracyCommand, TenantProvisionCommand
- `app/Providers/RepositoryServiceProvider.php`
- `database/migrations/tenant/` — 15 個 tenant-specific migrations（categories → schema_metadata）

**尚未建立：** Phase 2 的 Build Engine、Phase 3 的 SaaS / subscription 相關模組。實作新功能前先 `Glob` 確認對應目錄是否存在。

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

# Golden accuracy test（打真實 LLM API）
php artisan golden:run --limit=10   # 快速煙霧測試
php artisan golden:run              # 全部 150 筆

# Demo 租戶 seed
php artisan db:seed --class=DemoSeeder
```

## 技術棧

| 層級 | 技術 |
|------|------|
| Backend | PHP + Laravel（純 API，回傳 JSON） |
| Database | MySQL（DB-per-tenant 多租戶隔離） |
| AI | Apertis（OpenAI-compatible）+ gpt-4.1-mini + function calling |
| Frontend | Blade + Alpine.js + Axios（同一 Laravel 專案內前後端分離） |
| UI 設計 | Claude DESIGN.md（根目錄 `DESIGN.md`） |
| Auth | Laravel Sanctum（API token），OAuth2 未來再導入 |
| Cache | Redis（LLM 回應快取，需支援 tag） |

## 架構要點

- **前後端分離：** `Controllers/Api/` 回傳 JSON 處理業務邏輯，`Controllers/Web/` 只回傳 Blade view，不碰資料庫
- **DB-per-tenant：** 每個客戶獨立 MySQL DB，主資料庫存平台運營資料
- **API-first：** Blade 頁面透過 Axios 呼叫自己的 `/api/*` 端點
- **Blade 元件化：** 所有 UI 用 `<x-chat.bubble>` 等巢狀命名空間的 Blade Component
- **信心度分層：** Chat-to-query 的核心機制——高（> 95%）直接回答、中（70-95%）附提示、低（< 70%）不回答改引導釐清
- **Event-driven logging：** `QueryExecuted` event + `LogQueryListener`，query log 不在 controller 裡寫
- **Golden Test Suite：** `tests/Golden/GoldenQueryEngineTest.php` + `tests/Golden/Fixtures/` 用 data-driven 方式校準 QueryEngine 準確率，`php artisan golden:run` 可跑精度測試

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

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- phpunit/phpunit (PHPUNIT) - v12
- alpinejs (ALPINEJS) - v3
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `laravel-best-practices` — Apply this skill whenever writing, reviewing, or refactoring Laravel PHP code. This includes creating or modifying controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes, and Eloquent queries. Triggers for N+1 and query performance issues, caching strategies, authorization and security patterns, validation, error handling, queue and job configuration, route definitions, and architectural decisions. Also use for Laravel code reviews and refactoring existing Laravel code to follow best practices. Covers any task involving Laravel backend PHP code patterns.
- `tailwindcss-development` — Always invoke when the user's message includes 'tailwind' in any form. Also invoke for: building responsive grid layouts (multi-column card grids, product grids), flex/grid page structures (dashboards with sidebars, fixed topbars, mobile-toggle navs), styling UI components (cards, tables, navbars, pricing sections, forms, inputs, badges), adding dark mode variants, fixing spacing or typography, and Tailwind v3/v4 work. The core use case: writing or fixing Tailwind utility classes in HTML templates (Blade, JSX, Vue). Skip for backend PHP logic, database queries, API routes, JavaScript with no HTML/CSS component, CSS file audits, build tool configuration, and vanilla CSS.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

</laravel-boost-guidelines>
