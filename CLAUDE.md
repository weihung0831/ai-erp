# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 語言規範

- 所有回應一律使用**繁體中文**，技術用詞可使用英文

## Git 規則

- **禁止擅自 commit 或 push**，所有 commit 和 push 操作必須經過使用者明確同意後才能執行

## 專案概述

AI ERP 系統，目前處於初始建置階段。

## Git Submodules

- `awesome-design-md/` — 來自 [VoltAgent/awesome-design-md](https://github.com/VoltAgent/awesome-design-md) 的 DESIGN.md 集合，供 AI agent 產生一致 UI 時作為設計規範參考

### Submodule 常用指令

```bash
# clone 後初始化 submodule
git submodule update --init --recursive

# 更新 submodule 到最新 commit
git submodule update --remote awesome-design-md
```

## 使用 DESIGN.md

`awesome-design-md/design-md/{brand}/README.md` 包含各品牌的設計系統。每份 DESIGN.md 遵循 [Google Stitch 格式](https://stitch.withgoogle.com/docs/design-md/format/)，涵蓋：色彩、字型、元件樣式、佈局、陰影、響應式行為、Agent Prompt Guide。

使用方式：將目標品牌的 DESIGN.md 複製到專案根目錄，AI agent 即可依據該設計規範產生 UI。
