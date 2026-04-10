---
title: 多輪平行審查工作流（多文件規格集）
date: 2026-04-11
category: best-practices
module: documentation-workflow
problem_type: best_practice
component: documentation
severity: high
related_components:
  - development_workflow
  - tooling
applies_when:
  - 建立 >5 份彼此交叉引用的規格文件
  - 文件需作為 source-of-truth（已核准狀態）
  - Greenfield 專案基礎文件
  - 多階段實作會衍生自此文件
tags:
  - multi-round-review
  - parallel-subagents
  - documentation-quality
  - cross-doc-consistency
  - iterative-refinement
  - design-docs
  - regression-review
---

# 多輪平行審查工作流（多文件規格集）

## Context

當建立一組協同運作的規格文件（產品設計、架構、設計模式、UI 規範、元件庫、分階段實作規格），跨文件一致性是最主要的風險。單一文件可以自我一致，但仍會以微妙但關鍵的方式與其他兄弟文件矛盾：

- 認證策略漂移（一份寫 OAuth2，另一份寫 Sanctum）
- 數值不一致（元件庫宣稱 30 個，實際定義 42 個）
- Orphan endpoint（後端 spec 定義了 API，前端 spec 從未呼叫）
- Renumbering 後 cross-reference 斷裂
- 問題已在其他文件解決，但原文件的「待解決問題」未清除
- 前一輪修復引入的重複 section 編號

單輪審查——即使是認真的作者——幾乎必定漏掉這些問題。更糟的是，**修復本身會引入新問題**（renumbering 破壞錨點、澄清一份文件反而與另一份矛盾）。收斂的唯一可靠路徑是**迭代式平行多輪審查**：平行分派審查 agent、收集發現、套用修復、再次分派。重複直到品質 plateau。

此 pattern 專門適用於多文件規格集（通常 >5 個檔案），文件間必須互相一致才有意義，且不一致的成本很高（已核准的 spec、source-of-truth 文件、greenfield 基礎）。

## Guidance

### 1. 依關注點切分審查工作

**不要**派一個 agent「審查所有東西」。按關注點切分能產生更深的發現，同時最大化平行度。14 個規格檔案的建議切分：

- **Agent A — 設計模式文件：** Repository/Service/Factory/DTO 正確性、SOLID 遵循、命名一致性
- **Agent B — UI 規範 / 視覺設計：** Token 一致性（色彩、間距、字型）、元件覆蓋率 vs. 元件庫、響應式規則
- **Agent C — 元件庫：** 每個元件都有 props/slots/events/範例、元件數量與其他文件引用一致、無重複
- **Agent D — 分階段實作規格（後端/前端）：** Endpoint 清單一致性、依賴圖、跨階段的 retry/timeout/auth 一致性

後續輪次加入：

- **Agent E — 跨文件一致性：** 讀取**所有**文件，檢查文件 X 的主張 vs. 文件 Y 的主張
- **Agent F — 連結與錨點驗證：** 每個 cross-reference 有效、每個前端呼叫的 API 存在於後端、每個 UI 規範引用的元件存在於元件庫

### 2. 每個審查 agent 都要檢查的五軸

無論切分為何，每個 agent 都以相同的五軸回報：

1. **Consistency** — 文件自我一致且與兄弟文件一致嗎？
2. **Completeness** — 有任何 stub、TBD，或承諾但未寫的章節嗎？
3. **Clarity** — 新進工程師會誤讀任何章節嗎？
4. **Scope** — 有超出本文件宣告目的的內容嗎？
5. **Feasibility** — 有任何無法實作的規格嗎？

發現應以扁平 list 回傳 `{severity, location, issue, suggested_fix}`，方便修復階段快速分類。

### 3. 修復 → 再審查迴圈

```
Round N:
  1. 平行分派 N 個審查 agent（明確切分 + 五軸 checklist）
  2. 收集所有發現到單一 triage list
  3. 去重並排優先級（blocker > major > minor > nit）
  4. 單次套用修復（批次，不要一條一條）
  5. 記錄該輪品質分數（每 slice 分別記錄再平均）
  6. 若分數 plateau：STOP。否則：Round N+1。
```

關鍵：**每一輪修復之後必須再跑一輪審查**，因為修復常引入新問題：renumbering 破壞錨點、改寫一段反而與另一份矛盾、新增 endpoint 製造新的 orphan。

### 4. 何時停止——Plateau 訊號

當品質分數在輪次間不再有意義的改善，**且**無 blocker/major 問題剩餘時停止。實務上「有意義的改善」大約是 10 分制上 ~0.3+。若 Round N 得 9.2 且 Round N+1 得 9.3 只剩 nit 級發現，表示已收斂。不要追求 10/10——邊際報酬驟降，而 nit 永遠存在。

### 5. 要特別盯的問題分類

這些會跨專案重複出現，在後期輪次值得特別關注：

- **跨文件矛盾** — auth 策略、資料庫選擇、部署目標、retry 次數、timeout
- **數值不一致** — 一份文件「42 個元件」，另一份「30 個」
- **Cross-reference 斷裂** — `#section-3.2` renumbering 後失效
- **Orphan endpoint** — API 定義了但沒有任何前端 spec 呼叫
- **未定義的前端呼叫** — 前端呼叫 `POST /token/refresh` 但後端 spec 沒這個 endpoint
- **重複 section 編號** — 通常由前一輪修復插入 section 但未 renumber 兄弟造成
- **Markdown 結構 bug** — H2 在 H3 section 裡、code fence 未關閉、list 縮排漂移
- **Stale 待解決問題** — 「TBD」「Open questions」章節留著但問題已在其他文件回答
- **Convenience endpoint** — 定義了但從未被呼叫（orphan 的特例，常能在多輪中存活因為「看起來」屬於系統的一部分）

## Why This Matters

來自 14 份 AI ERP 規格集審查的真實數據：

| Round | 焦點 | 品質分數 | 值得注意的發現 |
|-------|------|----------|----------------|
| 1 | 4 個平行 agent：設計模式 / UI / 元件 / 前端 specs | 7.0 – 8.0 / 10 | ~50 個問題分散在 4 個 slice |
| 2 | 修復後再審查同樣 4 個區域 | 8.0 – 9.0 / 10 | 抓到新問題**以及**第 1 輪修復引入的問題（例如章節編號被插入動作破壞） |
| 3 | 全文件一致性 + 連結驗證 | 8.5 / 10 | OAuth2 vs Sanctum 矛盾、30 vs 42 元件數量、Phase 3 retry 次數不一致 |
| 4 | 最終驗證 | 9.2 → 9.3 / 10 | Orphan `token/refresh`（呼叫但未定義）、重複 section 編號、H2 位置錯誤、`quick-actions` endpoint 定義但前端從未呼叫、stale 待解決問題 |

**從數據觀察到的重點：**

- Round 1 單獨就會把 ~50 個問題 ship 到「最終版」文件
- Round 2 即使對已審過的區域**仍然找到**新問題——修復引入了新缺陷
- 跨文件審查（Round 3）抓到任何單文件審查都抓不到的矛盾，因為沒有任何一個 agent 能同時看到所有文件
- Round 4 抓到前三輪都漏的問題（orphan endpoint、markdown 結構 bug）——這就是為什麼 3 輪很少夠
- 9.2 → 9.3 是 plateau 訊號；繼續就是邊際報酬驟降
- 最終出貨品質 9.3/10，單一 clean commit，總計 4 輪審查

沒有這個迴圈，實際結果大概會是 ~7.5/10 的文件，帶著幾週後才會在實作時浮現的內嵌矛盾，修復成本遠高於前置的 4 輪審查。

## When to Apply

**適用此 pattern：**

- 規格集 **>5 份文件**且彼此交叉引用
- 文件有**大量 cross-reference**（API ↔ 前端、元件 ↔ UI 規範、設計模式 ↔ 實作）
- **Greenfield 專案**基礎——早期不一致會複利
- **高風險 / source-of-truth** 文件（已核准的 spec、架構決策、契約文件）
- 多階段實作會衍生自文件（不一致會被複製到程式碼）

**跳過此 pattern：**

- 小的文件編輯或錯字修正
- 單一檔案修改
- 低風險草稿或 brainstorming 文件
- 即將被重寫的內部工作筆記
- 沒有 cross-reference 的文件

成本（4 輪 × N 個平行 agent）只有在 ship 不一致的下游成本很高時才值得。

## Examples

### 範例：Round 1 平行分派 prompt

```
You are one of 4 parallel review agents for a multi-document specification set.

Your slice: docs/design/design-pattern.md

Review against these 5 axes:
1. Consistency — internal + agreement with docs/design/ai-erp-platform.md
2. Completeness — stubs, TBDs, promised-but-missing sections?
3. Clarity — would a mid-level engineer misread anything?
4. Scope — anything out of scope for a design-pattern doc?
5. Feasibility — any pattern that cannot be built in Laravel as described?

Specific checks:
- Every Repository/Service/Factory example compiles conceptually
- DTO boundaries are consistent
- SOLID violations flagged
- Naming matches the naming convention section
- Cross-references to system-architecture.md resolve

Return findings as a flat list:
[{severity: blocker|major|minor|nit, location, issue, suggested_fix}]

Also return a quality score /10 for this doc.
```

### 跨文件矛盾——auth 策略

**Round 3 發現前：**
- `docs/architecture/system-architecture.md`：「使用 OAuth2 + JWT token」
- `docs/spec/01-phase1-backend.md`：「安裝 Laravel Sanctum 做 API 認證」

**修復後：**
- 兩份文件都指 Sanctum；架構文件更新為實際選擇；`token/refresh` endpoint 從前端規格移除（OAuth2 artifact）。

### 數值不一致——元件數量

**Round 3 發現前：**
- `docs/design/ui-design-spec.md`：「使用元件庫定義的 30 個 Blade components」
- `docs/spec/00-component-library.md`：實際定義 42 個元件

**修復後：**
- UI spec 更新為「42 個」；審查 checklist 新增 grep-based 檢查供後續輪次。

### Orphan endpoint

**Round 4 發現前：**
- `01-phase1-backend.md` 定義 `POST /api/quick-actions/{id}/execute`
- 沒有任何前端 spec 呼叫此 endpoint

**修復後：**
- 移除 endpoint（YAGNI），或更新前端 spec 呼叫它。本次專案選擇移除，因為 UI 沒有對應的操作面。

### 重複 section 編號

**Round 4 發現前：**
- `06-phase3-frontend.md` 內有兩個 section 編號為 `4.3`，因為 Round 2 的修復插入新 section 但未 renumber 兄弟。

**修復後：**
- 所有下游 section 重新編號；其他文件的 cross-reference 在同一次 pass 內更新。

### Markdown 結構 bug

**Round 4 發現前：**
- `02-phase1-frontend.md` 有一個 `## Axios 設定` 插在 section 3 和 section 4–7 中間，破壞 H2 層級。

**修復後：**
- Axios 設定區塊移到 section 7 之後。

### Stale 待解決問題

**Round 4 發現前：**
- `docs/design/ai-erp-platform.md` 保留「待解決問題」章節列出 3 個問題，但 Round 1–3 已在架構和 Phase 1 規格文件中回答。

**修復後：**
- 移除章節；交叉連結指向各決策實際記錄的位置。

## Related

無（這是 `docs/solutions/` 的第一筆學習）。
