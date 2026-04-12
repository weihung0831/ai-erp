# UI 設計規範

日期：2026-04-12
狀態：已核准
依據：[DESIGN.md](../../DESIGN.md)（Spotify 設計系統）

## 總覽

本產品的 UI 基於 Spotify 的深色設計系統，以沉浸式深色背景、功能性綠色強調、pill 形按鈕為核心。此文件定義產品專屬的元件樣式，所有 Blade Component 依此規��實作。

## 色彩應用

### 主色調（來自 [DESIGN.md](../../DESIGN.md)）

| 角色 | 色碼 | 使用場景 |
|------|------|----------|
| 頁面背景 | `#121212`（Near Black） | 所有頁面的底色 |
| 卡片背景 | `#181818`（Dark Surface） | 聊天容器、表格容器、表單卡片 |
| 互動表面 | `#1f1f1f`（Mid Dark） | 輸入框、互動元素背景 |
| 主要文字 | `#ffffff` | 標題、重要數字 |
| 次要文字 | `#b3b3b3`（Silver） | 說明文字、標籤 |
| 輔助文字 | `#7c7c7c` | 時間戳記、metadata |
| 品牌色 | `#1ed760`（Spotify Green） | 確認按鈕、重要 CTA、active 狀態 |
| 品牌 hover | `#1fdf64` | hover 狀態 |
| 錯誤色 | `#f3727f`（Negative Red） | 錯誤訊息、驗證失敗 |
| 警告色 | `#ffa42b`（Warning Orange） | 警告狀態 |
| 邊框 | `#333333` | 分隔線���容器邊界 |

### 信心度顏色

| 信心度 | 背景 | 文字 | 說明 |
|--------|------|------|------|
| 高（> 95%） | `#1a3328` | `#1ed760` | 可信賴的結果 |
| 中（70-95%） | `#33291a` | `#ffa42b` | 建議確認 |
| 低（< 70%） | `#331a1e` | `#f3727f` | 需要釐清 |

## 字型

DESIGN.md 原始字型為 SpotifyMixUI / SpotifyMixUITitle（CircularSp 家族，非公開）。本專案使用���下替代字型：

| 用途 | 字型（替代） | 原始（DESIGN.md） | 大小 | 行高 |
|------|-------------|-------------------|------|------|
| 區塊標題 | Noto Sans TC Bold | SpotifyMixUITitle | 24px | 1.2 |
| 功能標題 | Noto Sans TC Semibold | SpotifyMixUI | 18px | 1.3 |
| 正文 | Noto Sans TC | SpotifyMixUI | 16px | 1.5 |
| 小字 | Noto Sans TC | SpotifyMixUI | 14px | 1.5 |
| 數據數字 | Noto Sans TC Bold | SpotifyMixUI | 32px | 1.1 |
| 按鈕 | Noto Sans TC Bold | SpotifyMixUI | 14px | 1.0 |
| 程式碼/SQL | JetBrains Mono | — | 14px | 1.5 |

**重要：** 不使用 serif 字型。所有文字統一使用 sans-serif。

## ���件設計規範

### 聊天氣泡（`<x-chat.bubble>`）

**AI 訊息：**
- 背景：`#181818`（Dark Surface）
- 圓角：8px
- 左側留 3px 品牌色邊條（`#1ed760`）
- 內距：16px
- 最大寬度：80%

**使用者訊息：**
- 背景：`#252525`（Dark Card）
- 圓角：8px
- 內距：12px 16px
- 最大寬度：70%
- 靠右對齊

**系統訊息（錯誤/提示）：**
- 背景：透明
- 文字色：`#7c7c7c`
- 字型大小：14px
- 置中對齊

### 數據卡片（`<x-data.stat-card>`）

- 背景：`#181818`
- 圓角：8px
- 內距：20px
- 標籤：`#7c7c7c`，14px
- 數值：`#ffffff`，32px，Bold
- 比較指標：綠色（上升 `#1ed760`）/ 紅色（下降 `#f3727f`），14px

### 資料表格（`<x-data.table>`）

- 表頭背景：`#272727`
- 表頭文字：`#b3b3b3`，14px，Bold
- 表格列背景：交替 `#181818` / `#1f1f1f`
- 表格列 hover：`#252525`
- 圓角：8px（外框）
- 內距：12px 16px（每個 cell）
- 金額欄位靠右對齊，使用千分位格式

### 表單輸入框（`<x-form.input>`）

- 背景：`#1f1f1f`
- 邊界：inset shadow（`rgb(18,18,18) 0px 1px 0px, rgb(124,124,124) 0px 0px 0px 1px inset`）
- 圓角：8px
- 內��：10px 14px
- 字型：16px
- Focus：`box-shadow: 0 0 0 2px #539df5`（Announcement Blue）
- 錯誤邊界：`box-shadow: 0 0 0 2px #f3727f`
- 錯誤訊息：`#f3727f`，14px

### 按鈕（`<x-ui.button>`）

**主要按鈕（CTA）：**
- 背景：`#1ed760`（Spotify Green）
- 文字：`#000000`
- 圓角：9999px（pill 膠囊形）
- 內距：10px 24px
- 字重：700
- 字距：0.5px
- Hover：`#1fdf64`
- Active：`#1db954` + scale(0.97)

**次要按鈕：**
- 背景：`#1f1f1f`
- 文字：`#ffffff`
- Hover：`#252525`

**文字按鈕：**
- 背景：透明
- 文字：`#b3b3b3`
- Hover：文字變 `#ffffff`

### 信心度標籤（`<x-chat.confidence>`）

- 圓角：9999px（pill）
- 內距：4px 10px
- 字型：12px，Bold
- 高信心：背景 `#1a3328`，文字 `#1ed760`
- 中信心：背景 `#33291a`，文字 `#ffa42b`
- 低信心：背景 `#331a1e`，文字 `#f3727f`

### 側邊導覽列（`<x-layout.sidebar>`）

- 寬度：260px
- 背景：`#0d0d0d`
- 文字：`#b3b3b3`（inactive），`#ffffff`（active，weight 700）
- 選取項目背景：`#1f1f1f`
- Hover 背景：`#1f1f1f`
- 分隔線：`#282828`
- Logo 區域：20px 內距，font-weight 700

### 頂部導覽列（`<x-layout.header>`）

- 高度：56px
- 背景：`#181818`
- 底部邊界：`1px solid #333333`
- 內距：0 24px

### Modal（`<x-ui.modal>`）

- 背景遮罩：`rgba(0, 0, 0, 0.7)`
- Modal 背景：`#181818`
- 圓角：8px
- 陰影：`rgba(0,0,0,0.5) 0px 8px 24px`（heavy shadow）
- 內距：24px
- 最大寬度：560px

### 快捷按鈕（`<x-chat.quick-actions>`）

- 背景：`#1f1f1f`
- 邊界：`1px solid #333333`
- 圓角：9999px（pill）
- 內距：8px 16px
- 字型：14px
- Hover 背景：`#252525`，border 變 `#4d4d4d`
- 排列：水平，間距 8px

### Loading Skeleton

- 背景：`#252525` 到 `#272727` 漸層動畫（shimmer）
- 圓角：與對應元件一致
- 動畫：左到右 shimmer，1.5 秒循環

### Toast / 通知（`<x-ui.toast>`）

- 位置：右上角，距離頂部 16px、右側 16px
- 背景：`#252525`
- 文字：`#ffffff`
- 圓角：9999px（pill）
- 內距：12px 16px
- 自動消失：3 秒
- 陰��：heavy shadow
- 成功 icon：`#1ed760`
- 錯誤 icon：`#f3727f`

### Tooltip

- 背景：`#252525`
- 文字：`#ffffff`，12px
- 圓角：4px
- 內距：4px 8px
- 陰影：medium shadow（`rgba(0,0,0,0.3) 0px 8px 8px`）
- 延遲��hover 300ms 後顯示

### Dropdown 選單（`<x-ui.dropdown>`）

- 背景：`#181818`
- 陰��：heavy shadow
- 圓角：8px
- 選項 hover 背景：`#1f1f1f`
- 選項文字：`#ffffff`，14px
- 分隔線：`#333333`
- 出現動畫：fade in，150ms

## 響應式斷點

| 名稱 | 寬度 | 變化 |
|------|------|------|
| Desktop | ≥ 1024px | 側邊欄 + 主內容區 |
| Tablet | 768px - 1023px | 側邊欄可收合，點擊漢堡選單展開 |
| Mobile | < 768px | 側邊欄隱藏，底部導覽列，聊天佔滿全寬 |

## 陰影系統

Spotify 使用重陰影（heavy shadows），在深色背景上需要高透明度才能可見：

| 用途 | 值 |
|------|------|
| 預設 | 無 border/shadow — 深色背景靠色差建立層次 |
| 卡片 hover | `rgba(0,0,0,0.3) 0px 8px 8px` |
| Modal / 選單 | `rgba(0,0,0,0.5) 0px 8px 24px` |
| 輸入框 | `rgb(18,18,18) 0px 1px 0px, rgb(124,124,124) 0px 0px 0px 1px inset` |
| Focus | `0 0 0 2px #539df5`（Announcement Blue） |
| Brand | `0 0 0 2px #1ed760` |

## 間距系統

基於 8px 基數：

| 名稱 | 值 | 用途 |
|------|------|------|
| xs | 4px | 元素內微間距 |
| sm | 8px | 元素間緊密間距 |
| md | 16px | 標準間距 |
| lg | 24px | 區塊間距 |
| xl | 32px | 大區塊間距 |
| 2xl | 48px | 頁面級間距 |

## 圓角系統

Spotify 的 pill-and-circle 幾何語言：

| 用途 | 值 |
|------|------|
| Badge | 9999px（pill） |
| 按鈕 | 9999px（pill） |
| 卡片/容器 | 8px |
| 輸入框 | 8px |
| 頭像/play 按鈕 | 50%（circle） |
| Tooltip | 4px |

## Dark Mode（唯一模式）

本產品僅使用深色模式，無 light mode 切換。`:root` 即為深色主題，不提供主題切換功能。

## 動畫

| 元素 | 動畫 | 時間 |
|------|------|------|
| 聊天訊息出現 | fade in + slide up | 200ms |
| AI 打字中 | 三個 6px 圓點（品牌綠色），依序上下跳動 4px | 每點 600ms，錯開 200ms |
| 按鈕 hover | 背景色漸變 | 150ms |
| 按鈕 active | scale(0.97) | 80ms |
| Modal 出現 | fade in + scale(0.95 → 1) | 200ms |
| 側邊欄展開/收合 | slide left/right | 200ms |
| 信心度標籤出現 | fade in | 150ms |
| Toast 出現 | fade in + slide right | 200ms |
