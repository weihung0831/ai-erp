# AI ERP

以 AI 驅動的 ERP 系統，具備設計系統感知的 UI 生成能力。

[English](README.md)

## 功能特色

- 運用 [DESIGN.md](https://stitch.withgoogle.com/docs/design-md/overview/) 檔案，讓 AI agent 產生一致且符合品牌風格的 UI
- 內含 59 套從真實網站萃取的設計系統（Stripe、Linear、Vercel、Notion 等）

## 快速開始

### 前置需求

- Git >= 2.13（需支援 submodule）

### 安裝

```bash
git clone <repo-url>
cd ai-erp
git submodule update --init --recursive
```

## 專案結構

```
ai-erp/
├── awesome-design-md/       # Submodule：精選 DESIGN.md 集合
│   └── design-md/{brand}/   # 各品牌設計系統檔案
└── CLAUDE.md                # AI agent 指引
```

## 使用 DESIGN.md

`awesome-design-md/design-md/` 下的每個品牌目錄包含遵循 [Google Stitch DESIGN.md 格式](https://stitch.withgoogle.com/docs/design-md/format/) 的設計系統，涵蓋：

- 色彩調色盤與語意角色
- 字型層級
- 元件樣式（按鈕、卡片、輸入框、導覽列）
- 佈局與間距原則
- 深度與層次系統
- 響應式行為與 breakpoints
- Agent prompt 指南

**使用方式：** 將目標品牌的 `DESIGN.md` 複製到專案根目錄，接著指示 AI agent 依據該規範產生 UI。

## 授權

本專案包含 [awesome-design-md](https://github.com/VoltAgent/awesome-design-md) 作為 submodule，採用 [MIT 授權](awesome-design-md/LICENSE)。
