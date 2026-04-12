# UI 設計規範

日期：2026-04-11
狀態：已核准
依據：[DESIGN.md](../../DESIGN.md)（Claude 設計系統）

## 總覽

本產品的 UI 基於 Claude (Anthropic) 的設計系統，以溫暖、專業、易於閱讀為核心。此文件定義產品專屬的元件樣式，所有 Blade Component 依此規範實作。

## 色彩應用

### 主色調（來自 [DESIGN.md](../../DESIGN.md)）

| 角色 | 色碼 | 使用場景 |
|------|------|----------|
| 頁面背景 | `#f5f4ed`（Parchment） | 所有頁面的底色 |
| 卡片背景 | `#faf9f5`（Ivory） | 聊天容器、表格容器、表單卡片 |
| 主要文字 | `#141413` | 標題、重要數字 |
| 次要文字 | `#5e5d59` | 說明文字、標籤 |
| 輔助文字 | `#87867f` | 時間戳記、metadata |
| 品牌色 | `#c96442`（Terracotta） | 送出按鈕、重要 CTA |
| 強調色 | `#d97757`（Coral） | 連結、hover 狀態 |
| 錯誤色 | `#b53333` | 錯誤訊息、驗證失敗 |
| 邊框 | `#e8e6dc` | 卡片邊框、分隔線 |

### 信心度顏色

| 信心度 | 顏色 | 說明 |
|--------|------|------|
| 高（> 95%） | `#2d6a4f`（深綠） | 可信賴的結果 |
| 中（70-95%） | `#d97757`（Coral） | 建議確認 |
| 低（< 70%） | `#b53333`（Crimson） | 需要釐清 |

## 字型

DESIGN.md 原始字型為 Anthropic Serif / Sans / Mono（非公開字型）。本專案使用以下 CJK 替代字型，保持相近的視覺風格：

| 用途 | 字型（替代） | 原始（DESIGN.md） | 大小 | 行高 |
|------|-------------|-------------------|------|------|
| 頁面標題 | Noto Serif TC | Anthropic Serif | 28px | 1.2 |
| 區塊標題 | Noto Sans TC Bold | Anthropic Sans Bold | 20px | 1.3 |
| 正文 | Noto Sans TC | Anthropic Sans | 16px | 1.6 |
| 小字 | Noto Sans TC | Anthropic Sans | 14px | 1.5 |
| 數據數字 | Noto Sans TC Bold | Anthropic Sans Bold | 32px | 1.1 |
| 程式碼/SQL | JetBrains Mono | Anthropic Mono | 14px | 1.5 |

## 元件設計規範

### 聊天氣泡（`<x-chat.bubble>`）

**AI 訊息：**
- 背景：`#faf9f5`（Ivory）
- 邊界：`box-shadow: 0px 0px 0px 1px #e8e6dc`
- 圓角：12px
- 左側留 4px 品牌色邊條（`#c96442`）
- 內距：16px
- 最大寬度：80%

**使用者訊息：**
- 背景：`#e8e6dc`
- 圓角：12px
- 內距：12px 16px
- 最大寬度：70%
- 靠右對齊

**系統訊息（錯誤/提示）：**
- 背景：透明
- 文字色：`#87867f`
- 字型大小：14px
- 置中對齊

### 數據卡片（`<x-data.stat-card>`）

- 背景：`#faf9f5`
- 邊界：`box-shadow: 0px 0px 0px 1px #e8e6dc`
- 圓角：8px
- 內距：20px
- 標籤：`#87867f`，14px
- 數值：`#141413`，32px，Bold
- 比較指標：綠色（上升 `#2d6a4f`）/ 紅色（下降 `#b53333`），14px

### 資料表格（`<x-data.table>`）

- 表頭背景：`#f0eee6`
- 表頭文字：`#4d4c48`，14px，Bold
- 表格列背景：交替 `#ffffff` / `#faf9f5`
- 表格列 hover：`#f0eee6`
- 邊界：`box-shadow: 0px 0px 0px 1px #e8e6dc`
- 圓角：8px（外框）
- 內距：12px 16px（每個 cell）
- 金額欄位靠右對齊，使用千分位格式

### 表單輸入框（`<x-form.input>`）

- 背景：`#ffffff`
- 邊界：`box-shadow: 0px 0px 0px 1px #e8e6dc`（ring shadow）
- 圓角：12px（符合 DESIGN.md generously rounded 規範）
- 內距：10px 14px
- 字型：16px
- Focus：`box-shadow: 0px 0px 0px 2px #3898ec`（Focus Blue，與 DESIGN.md 一致）
- 錯誤邊界：`box-shadow: 0px 0px 0px 2px #b53333`
- 錯誤訊息：`#b53333`，14px

### 按鈕（`<x-ui.button>`）

**主要按鈕（CTA）：**
- 背景：`#c96442`
- 文字：`#ffffff`
- 圓角：8px（符合 DESIGN.md 8-12px 規範）
- 內距：10px 20px
- Hover：`#d97757`
- Active：`#b5593b`
- Focus：`box-shadow: 0px 0px 0px 2px #3898ec`（Focus Blue）

**次要按鈕：**
- 背景：`#e8e6dc`
- 文字：`#4d4c48`
- Hover：`#d1cfc5`

**文字按鈕：**
- 背景：透明
- 文字：`#c96442`
- Hover：底線

### 信心度標籤（`<x-chat.confidence>`）

- 圓角：4px
- 內距：4px 8px
- 字型：12px，Bold
- 高信心：背景 `#d4edda`，文字 `#2d6a4f`
- 中信心：背景 `#fde8e0`，文字 `#d97757`
- 低信心：背景 `#f5c6cb`，文字 `#b53333`

### 側邊導覽列（`<x-layout.sidebar>`）

- 寬度：260px
- 背景：`#141413`（深色）
- 文字：`#b0aea5`（Warm Silver）
- 選取項目背景：`#30302e`
- 選取項目文字：`#ffffff`
- Hover 背景：`#30302e`
- 分隔線：`#30302e`
- Logo 區域內距：20px

### 頂部導覽列（`<x-layout.header>`）

- 高度：56px
- 背景：`#faf9f5`
- 底部邊界：`box-shadow: 0px 1px 0px 0px #e8e6dc`
- 內距：0 24px

### Modal（`<x-ui.modal>`）

- 背景遮罩：`rgba(20, 20, 19, 0.5)`
- Modal 背景：`#ffffff`
- 圓角：12px
- 陰影：`0 4px 24px rgba(20, 20, 19, 0.15)`
- 內距：24px
- 最大寬度：560px

### 快捷按鈕（`<x-chat.quick-actions>`）

- 背景：`#ffffff`
- 邊界：`box-shadow: 0px 0px 0px 1px #e8e6dc`
- 圓角：20px（膠囊形）
- 內距：8px 16px
- 字型：14px
- Hover 背景：`#f0eee6`
- 排列：水平，間距 8px

### Loading Skeleton

資料載入中的佔位元件，避免空白閃爍。

- 背景：`#e8e6dc` 到 `#f0eee6` 漸層動畫（shimmer）
- 圓角：與對應元件一致
- 高度：與對應元件一致
- 動畫：左到右 shimmer，1.5 秒循環

使用場景：聊天頁載入歷史對話、表格載入資料、數據卡片載入。

### Toast / 通知（`<x-ui.toast>`）

操作結果的即時回饋。

- 位置：右上角，距離頂部 16px、右側 16px
- 背景：`#141413`（深色）
- 文字：`#ffffff`
- 圓角：8px
- 內距：12px 16px
- 自動消失：3 秒
- 成功 icon：綠色勾 `#2d6a4f`
- 錯誤 icon：紅色叉 `#b53333`

### Tooltip

hover 時顯示的提示文字。

- 背景：`#141413`
- 文字：`#ffffff`，12px
- 圓角：4px
- 內距：4px 8px
- 位置：預設上方，空間不足自動翻轉
- 延遲：hover 300ms 後顯示

### 底部導覽列（Mobile）

Mobile 斷點（< 768px）時取代側邊欄。

- 高度：56px
- 背景：`#faf9f5`
- 頂部邊界：`box-shadow: 0px -1px 0px 0px #e8e6dc`
- 項目：icon + 文字，均分寬度
- 選取項目：品牌色 `#c96442`
- 非選取：`#87867f`

## 響應式斷點

| 名稱 | 寬度 | 變化 |
|------|------|------|
| Desktop | ≥ 1024px | 側邊欄 + 主內容區 |
| Tablet | 768px - 1023px | 側邊欄可收合，點擊漢堡選單展開 |
| Mobile | < 768px | 側邊欄隱藏，底部導覽列，聊天佔滿全寬 |

## 陰影系統

採用 DESIGN.md 的 ring-based shadow，以 `box-shadow: 0px 0px 0px 1px` 取代 `border`，創造更柔和的邊界感：

| 用途 | 值 |
|------|------|
| 預設邊界 | `box-shadow: 0px 0px 0px 1px #e8e6dc` |
| Hover | `box-shadow: 0px 0px 0px 1px #d1cfc5` |
| Focus | `box-shadow: 0px 0px 0px 2px #3898ec`（Focus Blue，DESIGN.md 唯一冷色，保留用於無障礙） |
| 強調邊界 | `box-shadow: 0px 0px 0px 1px #c96442` |

## 間距系統

基於 8px 基數（與 DESIGN.md 一致）：

| 名稱 | 值 | 用途 |
|------|------|------|
| xs | 4px | 元素內微間距（例外：icon 和文字間） |
| sm | 8px | 元素間緊密間距 |
| md | 16px | 標準間距 |
| lg | 24px | 區塊間距 |
| xl | 32px | 大區塊間距 |
| 2xl | 48px | 頁面級間距 |

## Dark Mode

基於 DESIGN.md 提供的暗色色彩，支援系統偏好自動切換（`prefers-color-scheme: dark`）或手動切換。

### 暗色色彩對應

| Light Mode | Dark Mode | 說明 |
|------------|-----------|------|
| `#f5f4ed`（Parchment） | `#141413`（Deep Dark） | 頁面背景 |
| `#faf9f5`（Ivory） | `#30302e`（Dark Surface） | 卡片背景 |
| `#141413`（Near Black） | `#ffffff` | 主要文字 |
| `#5e5d59`（Olive Gray） | `#b0aea5`（Warm Silver） | 次要文字 |
| `#87867f`（Stone Gray） | `#87867f`（Stone Gray） | 輔助文字（不變） |
| `#e8e6dc`（Border Warm） | `#30302e`（Border Dark） | 邊框 |
| `#f0eee6`（Border Cream） | `#3d3d3a`（Dark Warm） | 表頭背景 |
| `#ffffff` | `#30302e` | 輸入框背景 |
| `#e8e6dc`（Warm Sand） | `#3d3d3a`（Dark Warm） | 次要按鈕背景 |

### 不變色彩

以下色彩在 dark mode 中保持不變：

- 品牌色 `#c96442`（Terracotta）
- 強調色 `#d97757`（Coral）
- 錯誤色 `#b53333`（Crimson）
- Focus Blue `#3898ec`
- 信心度顏色（高 `#2d6a4f`、中 `#d97757`、低 `#b53333`）

### 實作方式

使用 CSS 變數，在 `<html>` 層級切換：

```css
:root {
    --bg-page: #f5f4ed;
    --bg-card: #faf9f5;
    --text-primary: #141413;
    --text-secondary: #5e5d59;
    --border: #e8e6dc;
}

@media (prefers-color-scheme: dark) {
    :root {
        --bg-page: #141413;
        --bg-card: #30302e;
        --text-primary: #ffffff;
        --text-secondary: #b0aea5;
        --border: #30302e;
    }
}
```

所有 Blade Component 使用 CSS 變數而非硬編碼色碼，確保 dark mode 自動生效。

### 開發優先級

先實作 light mode。Dark mode 作為後續體驗優化加入。

## Dropdown 選單（`<x-ui.dropdown>`）

- 觸發方式：點擊觸發按鈕
- 背景：`#ffffff`（dark: `#30302e`）
- 邊界：`box-shadow: 0px 0px 0px 1px #e8e6dc`（dark: `0px 0px 0px 1px #30302e`）
- 圓角：8px
- 內距：4px 0（選單容器）
- 選項內距：8px 16px
- 選項 hover 背景：`#f0eee6`（dark: `#3d3d3a`）
- 選項文字：`#141413`（dark: `#ffffff`），14px
- 分隔線：`box-shadow: 0px 1px 0px 0px #e8e6dc`（dark: `#30302e`）
- 最大高度：300px（超出捲動）
- 出現動畫：fade in + scale(0.95 → 1)，150ms

## 動畫

| 元素 | 動畫 | 時間 |
|------|------|------|
| 聊天訊息出現 | fade in + slide up | 200ms |
| AI 打字中 | 三個 6px 圓點，間距 4px，依序上下跳動 4px，easing: ease-in-out | 每點 600ms，錯開 200ms |
| 按鈕 hover | 背景色漸變 | 150ms |
| Modal 出現 | fade in + scale(0.95 → 1) | 200ms |
| 側邊欄展開/收合 | slide left/right | 250ms |
| 信心度標籤出現 | fade in | 150ms |
