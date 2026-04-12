# AI ERP

AI-powered ERP platform that replaces traditional ERP interfaces (forms, menus, reports) with a single chat interface. Customers operate their entire ERP through natural language conversation.

[繁體中文](README.zh-TW.md)

## Status

**Query backend complete; frontend integration in progress; write operations next.** Laravel 13 + Sanctum scaffolded, 42 Blade components implemented, Chat-to-query (read-only) backend with 17 APIs working end-to-end (including SSE streaming), Golden Test Suite at 150 cases with 100% pass rate.

What's built:
- `Controllers/Api/` — Auth, Chat, StreamChat (SSE), ChatHistory
- `Services/` — QueryEngine, OpenAiGateway, SqlValidator, ConfidenceEstimator, TenantManager, TenantDatabaseManager
- `Models/` — ChatHistory, Conversation, SchemaFieldRestriction, Tenant, User
- `Repositories/` — Contract + Eloquent implementation (Repository Pattern)
- DB-per-tenant with 15 tenant migrations + demo seeder
- Golden accuracy test suite (150 cases, 100% pass)

Next: Expand to Chat-to-operate (add INSERT/UPDATE/DELETE + confirmation flow + file upload + Dashboard).

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
| UI Design | [Claude DESIGN.md](DESIGN.md) |
| Auth | Laravel Sanctum (API token) |
| Cache | Redis (tag-aware, for LLM response caching) |

## Architecture

- **API-first** — `Controllers/Api/` returns JSON; `Controllers/Web/` returns Blade views that make Axios calls to `/api/*`
- **DB-per-tenant** — Each customer gets an isolated MySQL database
- **Blade components** — All UI built with namespaced components (`<x-chat.bubble>`, `<x-data.table>`, etc.)
- **Confidence tiers (queries)** — High (>95%): direct answer; Medium (70-95%): answer with caution; Low (<70%): clarify instead
- **Mandatory confirmation (writes)** — All INSERT/UPDATE/DELETE show a summary and require explicit user confirmation

## Documentation

- [Design Document](docs/design/ai-erp-platform.md) — Product design (approved)
- [System Architecture](docs/architecture/system-architecture.md) — Modules, database, API design
- [Design Patterns](docs/design/design-pattern.md) — Repository, Service, Factory, DTO, etc.
- [UI Design Spec](docs/design/ui-design-spec.md) — Component visual specs, dark mode, animations
- [Component Library](docs/spec/00-component-library.md) — Blade Component definitions
- [Backend Spec](docs/spec/01-phase1-backend.md) — Chat-to-Operate API (query + write + upload + dashboard)
- [Frontend Spec](docs/spec/02-phase1-frontend.md) — Chat interface pages

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
php artisan db:seed --class=DemoSeeder   # provision demo tenant + sample data
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
```

### Read the design first

Before writing any code, read these in order:

1. [Design Document](docs/design/ai-erp-platform.md) — what we're building and why
2. [System Architecture](docs/architecture/system-architecture.md) — modules, database, API
3. [Design Patterns](docs/design/design-pattern.md) — **required reading before writing any code**
4. [UI Design Spec](docs/design/ui-design-spec.md) — component visual specs, dark mode, animations
5. [Component Library Spec](docs/spec/00-component-library.md) — reference for which component to use

## License

This project includes [awesome-design-md](https://github.com/VoltAgent/awesome-design-md) as a submodule, licensed under [MIT](awesome-design-md/LICENSE).
