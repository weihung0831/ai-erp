# AI ERP

AI-powered ERP platform that lets customers build and query ERP systems through conversation.

[繁體中文](README.zh-TW.md)

## Status

**Design phase.** The Laravel application is not yet scaffolded — this repository currently contains design documents, architecture, specs, the UI design system, and a solutions knowledge base. Implementation follows the spec order defined in [CLAUDE.md](CLAUDE.md): starting with [00 Component Library](docs/spec/00-component-library.md), then [01 Phase 1 Backend](docs/spec/01-phase1-backend.md), [02 Phase 1 Frontend](docs/spec/02-phase1-frontend.md), and so on through Phase 3.

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
| AI | OpenAI GPT-4o + function calling |
| Frontend | Blade + Alpine.js + Axios |
| UI Design | [Claude DESIGN.md](DESIGN.md) |
| Auth | Laravel Sanctum (API token) |
| Cache | Redis (tag-aware, for LLM response caching) |

## Architecture

- **API-first** — `Controllers/Api/` returns JSON, `Controllers/Web/` returns Blade views via Axios calls to `/api/*`
- **DB-per-tenant** — Each customer gets an isolated MySQL database
- **Blade components** — All UI built with namespaced components (`<x-chat.bubble>`, `<x-data.table>`, etc.)

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
- [Component Library](docs/spec/00-component-library.md) — 42 Blade Component definitions
- [Solutions Knowledge Base](docs/solutions/) — Resolved issues and workflow patterns, organized by category with frontmatter metadata (`module`, `tags`, `problem_type`)

## Getting Started

### Read the design first

While the Laravel application is not yet scaffolded, start by reading the approved design:

```bash
git clone https://github.com/weihung0831/ai-erp.git
cd ai-erp
git submodule update --init --recursive
```

Then read, in order:

1. [Design Document](docs/design/ai-erp-platform.md) — what we're building and why
2. [System Architecture](docs/architecture/system-architecture.md) — modules, database, API
3. [Design Patterns](docs/design/design-pattern.md) — **required reading before writing any code**
4. [UI Design Spec](docs/design/ui-design-spec.md) — component visual specs, dark mode, animations (the basis for the component library)
5. [Component Library Spec](docs/spec/00-component-library.md) — the first implementation target, per the spec order defined in [CLAUDE.md](CLAUDE.md)

### Planned stack (for when implementation begins)

- PHP >= 8.2 + Composer
- MySQL >= 8.0
- Redis (tag-aware)
- Node.js (for frontend assets)
- OpenAI API key (GPT-4o)

## License

This project includes [awesome-design-md](https://github.com/VoltAgent/awesome-design-md) as a submodule, licensed under [MIT](awesome-design-md/LICENSE).
