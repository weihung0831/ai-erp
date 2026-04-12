# AI ERP

AI-powered ERP platform that lets customers build and query ERP systems through conversation.

[繁體中文](README.zh-TW.md)

## Status

**Phase 1 backend complete; frontend integration in progress.** Laravel 13 + Sanctum scaffolded, all 42 Blade components implemented, and the Chat-to-query backend pipeline (LLM → SQL generation → execution → response) is working end-to-end.

What's built:
- `Controllers/Api/` — Auth, Chat, StreamChat (SSE), ChatHistory, QuickAction, Admin (QueryLog, SchemaField)
- `Services/` — QueryEngine, OpenAiGateway, SqlValidator, ConfidenceEstimator, TenantManager, TenantDatabaseManager
- `Models/` — ChatHistory, Conversation, QueryLog, QuickAction, SchemaFieldRestriction, Tenant, User
- `Repositories/` — Contract + Eloquent implementation (Repository Pattern)
- DB-per-tenant with 15 tenant migrations + demo seeder
- Event-driven query logging, Golden accuracy test suite (150 cases)

Still to build: Phase 1 chat UI page, Phase 2 Build Engine, Phase 3 SaaS modules.

## What is this?

An ERP development company's product: instead of hiring developers to build custom ERP systems, customers describe their needs in natural language and AI generates the database schema, API, and UI automatically. Once built, users query their data through chat instead of navigating complex interfaces.

## Core Modules

| Module | For | What it does |
|--------|-----|-------------|
| **Chat-to-build** | Customer | Describe business needs in conversation, AI generates schema + UI |
| **Chat-to-query** | Same customer | Query business data through chat, replacing complex ERP navigation |

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
- **Blade components** — All UI built with namespaced components (`<x-chat.bubble>`, `<x-data.table>`, etc.). The component library (42 components) is complete and browsable at `/components`

## Development Phases

| Phase | Scope | Spec |
|-------|-------|------|
| 1 | Chat-to-query: natural language to SQL | [Backend](docs/spec/01-phase1-backend.md) / [Frontend](docs/spec/02-phase1-frontend.md) |
| 2 | Chat-to-build: conversation to schema + CRUD UI | [Backend](docs/spec/03-phase2-backend.md) / [Frontend](docs/spec/04-phase2-frontend.md) |
| 3 | SaaS: self-service signup, billing, knowledge feedback loop | [Backend](docs/spec/05-phase3-backend.md) / [Frontend](docs/spec/06-phase3-frontend.md) |

## Documentation

- [Design Document](docs/design/ai-erp-platform.md) — Product design (approved)
- [System Architecture](docs/architecture/system-architecture.md) — Modules, database, API design
- [Design Patterns](docs/design/design-pattern.md) — Repository, Service, Factory, DTO, etc.
- [UI Design Spec](docs/design/ui-design-spec.md) — Component visual specs, dark mode, animations
- [Component Library](docs/spec/00-component-library.md) — 42 Blade Component definitions (all implemented)

## Getting Started

### Prerequisites

- PHP **^8.3** + Composer
- Node.js 20+ (for Vite / Tailwind v4)
- MySQL 8+
- Redis (tag-aware) — required from Phase 1 for LLM response caching
- OpenAI-compatible API key (default: Apertis + gpt-4.1-mini) — required from Phase 1 for chat features

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

Open **http://localhost:8000/components** to browse the Blade component library.

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
5. [Component Library Spec](docs/spec/00-component-library.md) — already implemented; reference for which component to use

## License

This project includes [awesome-design-md](https://github.com/VoltAgent/awesome-design-md) as a submodule, licensed under [MIT](awesome-design-md/LICENSE).
