# AI ERP

AI-powered ERP system with design-system-aware UI generation.

[繁體中文](README.zh-TW.md)

## Features

- Leverages [DESIGN.md](https://stitch.withgoogle.com/docs/design-md/overview/) files so AI agents can generate consistent, brand-accurate UI
- Includes 59 ready-to-use design systems extracted from real-world websites (Stripe, Linear, Vercel, Notion, etc.)

## Getting Started

### Prerequisites

- Git >= 2.13 (submodule support)

### Installation

```bash
git clone <repo-url>
cd ai-erp
git submodule update --init --recursive
```

## Project Structure

```
ai-erp/
├── awesome-design-md/       # Submodule: curated DESIGN.md collection
│   └── design-md/{brand}/   # Per-brand design system files
└── CLAUDE.md                # AI agent instructions
```

## Using DESIGN.md

Each brand directory under `awesome-design-md/design-md/` contains a design system following the [Google Stitch DESIGN.md format](https://stitch.withgoogle.com/docs/design-md/format/), covering:

- Color palette & semantic roles
- Typography hierarchy
- Component styles (buttons, cards, inputs, navigation)
- Layout & spacing principles
- Depth & elevation system
- Responsive behavior & breakpoints
- Agent prompt guide

**Usage:** Copy a brand's `DESIGN.md` to your project root, then instruct your AI agent to follow it when generating UI.

## License

This project includes [awesome-design-md](https://github.com/VoltAgent/awesome-design-md) as a submodule, licensed under [MIT](awesome-design-md/LICENSE).
