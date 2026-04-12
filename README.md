# AI ERP

AI-powered ERP platform that replaces traditional ERP interfaces (forms, menus, reports) with a single chat interface. Customers operate their entire ERP through natural language conversation.

[繁體中文](README.zh-TW.md)

## Status

**Query + Dashboard complete; write operations next.** Laravel 13 + Sanctum scaffolded, 48 Blade components, 18 APIs (SSE streaming + Dashboard), Golden Test Suite 150 cases at 100% pass rate, Dashboard page with month/quarter/year metrics and trend comparison, chat page with Claude-style empty state and markdown rendering.

What's built:
- `Controllers/Api/` — Auth, Chat, StreamChat (SSE), ChatHistory, Dashboard
- `Controllers/Web/` — AuthPage, ChatPage, DashboardPage
- `Services/` — QueryEngine, OpenAiGateway, SqlValidator, ConfidenceEstimator, TenantManager, DashboardService
- `Models/` — ChatHistory, Conversation, SchemaFieldRestriction, Tenant, User
- `Repositories/` — Contract + Eloquent implementation (Repository Pattern)
- `Enums/` — ChatResponseType, ConfidenceLevel, UserRole, ValueFormat, AggregationType
- DB-per-tenant with 16 tenant migrations + demo seeder
- Dashboard: predefined queries (sales/finance/operations), month/quarter/year periods, trend %
- Chat: Claude-style centered welcome, markdown rendering (marked.js), inline Alpine.js UI
- Golden accuracy test suite (150 cases, 100% pass)

Next: Chat-to-operate (INSERT/UPDATE/DELETE + confirmation flow + file upload).

## What is this?

An ERP development company's product: instead of building custom forms and UI for every client, the team designs the database schema and deploys a universal AI chat interface. Customers do everything through chat — query data, create records, update information, delete entries. Bulk operations use file upload (Excel/CSV). A dashboard provides business overview at a glance.

## How it works

| Component | What it does | Replaces |
|-----------|-------------|----------|
| **AI Chat** | All CRUD operations via natural language | Traditional ERP forms, menus, list views |
| **Dashboard** | Business metrics overview | Traditional ERP report pages |
| **File Upload** | Bulk data import (Excel/CSV) | Manual row-by-row data entry |
| **Schema Service** | Team designs DB schema per client | Unchanged — this is the team's core expertise |

All write operations require user confirmation before execution. No silent writes.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP + Laravel (API-first, returns JSON) |
| Database | MySQL (DB-per-tenant isolation) |
| AI | Apertis (OpenAI-compatible) + gpt-4.1-mini + function calling |
| Frontend | Blade + Alpine.js + Axios |
| UI Design | [Spotify DESIGN.md](DESIGN.md) (dark-only) |
| Auth | Laravel Sanctum (API token) |
| Cache | Redis (tag-aware, for LLM response caching) |

## Architecture

- **API-first** — `Controllers/Api/` returns JSON; `Controllers/Web/` returns Blade views that make Axios calls to `/api/*`
- **DB-per-tenant** — Each customer gets an isolated MySQL database
- **Routing** — `/` → `/dashboard` (home), `/chat` (AI chat), `/login`, `/forgot-password`, `/reset-password/{token}`
- **Dashboard** — Standalone page with predefined Query Builder queries (not LLM-generated), period selector (month/quarter/year), trend comparison vs previous period
- **Chat** — Dual-state: Claude-style centered welcome (empty) → conversation mode (active); AI responses rendered as markdown (marked.js)
- **Blade + inline rendering** — 48 Blade components exist, but chat and dashboard pages render UI inline with Alpine.js (`x-for`, `x-if`) and CSS classes for dynamic content
- **Confidence tiers (queries)** — High (>95%): direct answer; Medium (70-95%): answer with caution; Low (<70%): clarify instead
- **Mandatory confirmation (writes)** — All INSERT/UPDATE/DELETE show a summary and require explicit user confirmation

## Documentation

- [Design Document](docs/design/ai-erp-platform.md) — Product design (approved)
- [System Architecture](docs/architecture/system-architecture.md) — Modules, database, API design
- [Design Patterns](docs/design/design-pattern.md) — Repository, Service, Factory, DTO, etc.
- [UI Design Spec](docs/design/ui-design-spec.md) — Component visual specs (Spotify dark-only), animations
- [Component Library](docs/spec/00-component-library.md) — Blade Component definitions
- [Backend Spec](docs/spec/01-phase1-backend.md) — Chat-to-Operate API (query + write + upload + dashboard)
- [Frontend Spec](docs/spec/02-phase1-frontend.md) — Dashboard page + Chat page (Claude-style + markdown) + write confirmation + file upload

## Getting Started

### Prerequisites

- PHP **^8.3** + Composer
- Node.js 20+ (for Vite / Tailwind v4)
- MySQL 8+
- Redis (tag-aware) — for LLM response caching
- OpenAI-compatible API key (default: Apertis + gpt-4.1-mini)

### Setup

```bash
git clone https://github.com/weihung0831/ai-erp.git
cd ai-erp
git submodule update --init --recursive

composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=DemoSeeder                # demo account: admin@example.com / admin@example.com
php artisan tenant:provision 1 --fresh --seed          # provision tenant DB with demo data
npm run build
```

### Run

```bash
# All-in-one: server + queue + logs + Vite watcher
composer run dev

# Or just the PHP dev server
php artisan serve
```

### Common commands

```bash
composer run test                # PHPUnit tests
./vendor/bin/pint --test         # lint check
./vendor/bin/pint                # lint auto-fix
npm run build                    # production asset build
npm run dev                      # Vite watch mode
php artisan view:clear           # clear compiled Blade cache
php artisan golden:run           # LLM accuracy test (150 cases, hits real API)
php artisan golden:run --limit=10  # quick accuracy smoke test
php artisan tenant:provision 1 --fresh --seed  # re-provision tenant DB
```

### Read the design first

Before writing any code, read these in order:

1. [Design Document](docs/design/ai-erp-platform.md) — what we're building and why
2. [System Architecture](docs/architecture/system-architecture.md) — modules, database, API
3. [Design Patterns](docs/design/design-pattern.md) — **required reading before writing any code**
4. [UI Design Spec](docs/design/ui-design-spec.md) — component visual specs (Spotify dark-only), animations
5. [Component Library Spec](docs/spec/00-component-library.md) — reference for which component to use

## License

This project includes [awesome-design-md](https://github.com/VoltAgent/awesome-design-md) as a submodule, licensed under [MIT](awesome-design-md/LICENSE).
