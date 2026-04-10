# 設計文件：AI ERP 平台 — Chat-to-Build + Chat-to-Query

產生方式：/office-hours
日期：2026-04-11
分支：main
儲存庫：weihung0831/ai-erp
狀態：已核准
模式：新創

## 問題陳述

ERP 開發公司（即本團隊）每次接案都從零幫客戶建 ERP 系統：設計 schema、寫程式、建 UI。每個客戶需求不同，導致大量重複但又略有差異的人工開發工作。客戶端則面臨兩個痛點：建系統貴又慢、建好後操作複雜老闆沒時間學。

AI 帶來結構性改變的機會：讓客戶自己用對話描述需求、AI 自動產生系統，同時讓使用者用對話查資料，取代複雜的 UI 操作。

## 需求證據

- 團隊本身就是 ERP 開發公司，每天在接客戶的開發需求，親身經歷這個痛點
- 客戶反覆提出類似需求（進銷存、報表、查詢），代表 pattern 明確可抽象化
- 老闆（公司決策者）主動提出要做這個產品，代表內部已有共識

## 現狀

- 客戶付開發費，團隊從零寫 code
- 每個案子都有重複的部分（CRUD、報表、權限），但因需求差異無法完全複用
- 系統交付後，客戶操作學習成本高，老闆通常不自己進系統查資料

## 目標用戶與產品定義

**目標客戶：** 需要 ERP 系統的中小企業（現有接案客戶為第一批）

**產品兩面：**

| 模組 | 面向 | 做什麼 | 取代什麼 |
|------|------|--------|----------|
| Chat-to-build | 客戶 | 用對話描述需求，AI 產生 schema + UI | 團隊的人工開發 |
| Chat-to-query | 同一客戶 | 用對話查資料、看報表 | 複雜的 ERP UI 操作 |

兩個模組是同一產品的兩面，服務同一批客戶，都是核心功能。

## 限制條件

- 查詢準確率分級要求：關鍵財務數據（營收、應收帳款）> 99%，一般查詢（庫存查詢、人員列表）> 95%。商業數據報錯數字信任直接歸零
- 台灣中小企業市場為初始目標，需支援在地商業邏輯（統一發票、營業稅、勞健保）
- 團隊有 PHP/Laravel 技術背景，現有客戶系統為 PHP + MySQL
- 需考慮多租戶架構（每個客戶一套獨立系統）
- 查詢回應延遲 < 5 秒（含 LLM 處理時間）
- 需符合台灣個人資料保護法，客戶商業數據嚴格隔離

## 前提假設

1. 目標客戶是現有 ERP 接案客戶（已有信任關係和需求理解）
2. Chat-to-build 面向客戶，用 AI 取代人工開發；Chat-to-query 面向同一客戶，用對話取代複雜 UI
3. 兩個模組都是核心，是同一產品的兩面
4. 過去的接案經驗是核心 domain knowledge 來源（知道各產業要什麼欄位、什麼流程）
5. 查詢準確率必須極高（關鍵財務 > 99%，一般 > 95%）
6. 台灣中小企業是初始市場

## 跨模型觀點

Claude subagent 獨立分析後提出：

- **Steelman：** 如果 AI 自己設計 schema，它就能完全理解整個系統。這形成正向循環——控制 schema 生成 = 控制查詢準確率。
- **關鍵洞察：** 團隊過去做的每一個案子都是 AI 的訓練資料。ERP domain knowledge（各產業的欄位 pattern、流程邏輯）是真正的護城河。
- **被挑戰的前提：** 原本的「兩模組平行開發」前提被挑戰，理由是學習信號分散。但在得知團隊是 ERP 開發公司後，這個挑戰不再成立——團隊已有大量需求知識，不需要從零學習市場。
- **48 小時原型建議：** 挑一個垂直場景（手搖飲店），固定 schema + 假資料，建自然語言查詢 chat。

## 考慮過的方案

### Approach A: 垂直場景 Chat-to-Query MVP
固定 schema + 模擬資料，建自然語言查詢 demo。2 週可完成。最快驗證但不展示 chat-to-build。

### Approach B: 台灣 ERP Domain Engine + Query
先建 ERP domain knowledge layer（台灣會計、進銷存、稅務），再做 query。4-6 週。建護城河但較慢拿到回饋。

### Approach C: 接現有客戶系統的 AI 前端
接上團隊已開發的客戶 ERP 系統，提供 AI 聊天介面查詢和建構。利用現有客戶關係和 schema 知識。

## 安全性與合規

- **認證：** Laravel Sanctum（API token）。OAuth2 + JWT 規劃於未來開放第三方整合時再導入
- **資料隔離：** 多租戶間嚴格隔離，任何查詢只能存取該租戶的資料
- **加密：** 資料庫連線 TLS，敏感欄位（薪資、成本）加密儲存
- **個資法合規：** 符合台灣個人資料保護法，提供資料匯出/刪除功能
- **AI 安全：** LLM 生成的 SQL 只允許 SELECT（read-only connection），禁止 DDL/DML 操作於 query 模組

## 推薦方案

**Approach C 為基礎，結合 B 的 domain engine 概念。**

理由：
- 團隊是 ERP 開發公司，已有客戶、已有 schema 知識、已有系統存取權——C 的風險對團隊幾乎不存在
- 過去接案累積的 pattern 可以抽象為 domain knowledge layer，這是長期護城河
- Chat-to-build 的正向循環：AI 自己設計的 schema，AI 查詢時準確率更高

### 建議路線圖

**Phase 1（MVP）：** 挑一個現有客戶的 ERP 系統，加上 Chat-to-query 功能。包含接入真實資料庫（schema mapping）、完整安全性。無時程壓力，做到品質達標再上線。驗收指標：同一個查詢任務，聊天完成時間 < 傳統 UI 的 50%，且準確率達標（關鍵財務 > 99%，一般 > 95%）。

**查詢回退策略（信心度分層）：**
- **高信心（> 95%）：** 直接回答，背景記錄 SQL 和結果供審計
- **中信心（70-95%）：** 回答但附提示「此查詢較複雜，建議確認」，可展開看 SQL
- **低信心（< 70%）：** 不回答，改為引導使用者釐清意圖（「你是要查 A 還是 B？」）

核心原則：寧可不回答，也不回答錯的數字。

**Phase 2（6-8 週）：** 從過去的接案經驗中抽取 domain knowledge，建 Chat-to-build。產出定義：AI 根據對話產生 MySQL schema + Laravel API endpoints + 基於 template 的 CRUD UI（類似 Laravel Nova scaffold）。完全客製化的前端界面不在此階段範圍。

**Phase 3（持續）：** 每個新客戶的系統都回饋到 domain knowledge，系統越用越聰明。從接案公司轉型為 SaaS 公司。

### 技術方向

| 層級 | 技術選擇 | 說明 |
|------|----------|------|
| Backend | PHP + Laravel | 團隊現有技術棧 |
| Database | MySQL | 團隊熟悉，現有客戶系統一致 |
| AI Layer | OpenAI GPT-4o + function calling | Text-to-SQL 準確率最高，PHP SDK 完整 |
| Frontend | Blade + Alpine.js + Axios | Laravel 原生模板，Alpine.js 管理聊天狀態，Axios 處理 API 請求 |
| UI 設計 | Claude DESIGN.md | 溫暖聊天介面風格，適合非技術用戶 |
| 多租戶 | DB-per-tenant | 見下方分析 |

### 多租戶策略分析

| 方案 | 優點 | 缺點 |
|------|------|------|
| schema-per-tenant | 資源共用、部署簡單 | migration 影響所有租戶、schema 衝突風險 |
| DB-per-tenant（建議） | 完全隔離、獨立備份/還原、Chat-to-build 可自由修改 schema | 運維成本較高、連線管理較複雜 |

建議 DB-per-tenant，因為 Chat-to-build 會為每個客戶產生不同的 schema，獨立 DB 避免租戶間 schema 衝突。

### 領域知識層定義

團隊過去接案累積的產業知識，以結構化形式儲存供 AI runtime 使用：

- **形式：** 結構化 JSON/YAML schema library，按產業分類（餐飲、製造、貿易...）
- **內容：** 各產業常見的 table 定義、欄位命名慣例、關聯關係、商業規則（如：營業稅 5%、發票格式）
- **使用方式：** 作為 LLM 的 system prompt context，Chat-to-build 時 AI 參考對應產業的 template 產生 schema；Chat-to-query 時 AI 參考 schema metadata 提高 SQL 準確率
- **成長機制：** 每個新客戶的系統經人工審核後回饋到 library

### 技術風險與應對

| 風險 | 嚴重度 | 應對策略 |
|------|--------|------------|
| Text-to-SQL 準確率不足 | 高 | 用 function calling 取代 raw SQL 生成；預定義常見查詢 template；SQL 執行前經 EXPLAIN 驗證；關鍵財務查詢加 human-in-the-loop 確認 |
| Chat-to-build UI 生成過於複雜 | 中 | Phase 2 限定 template-based scaffold（類似 Django admin），不做 free-form UI 生成 |
| LLM 成本失控 | 中 | 初步估算：每次查詢 ~2K tokens（$0.01-0.03/次），10 客戶 x 20 次/天 = $60-180/月。定價需覆蓋 LLM 成本 + 30% margin。考慮 cache 常見查詢、使用較小模型處理簡單查詢 |
| 多租戶資料洩漏 | 高 | DB-per-tenant 物理隔離；read-only DB connection for query；API 層 tenant ID 強制校驗 |

## 待解決問題

（無。定價模式已於 [Phase 3 後端規格](../spec/05-phase3-backend.md#訂閱方案設計) 決定為月繳/年繳訂閱制，四層級方案。）

## 成功標準

- [ ] 至少 1 個現有客戶使用 Chat-to-query，且日常使用（每週 > 5 次查詢）
- [ ] Chat-to-query 準確率：關鍵財務數據 > 99%，一般查詢 > 95%（以 golden test suite 驗證：關鍵財務 100+ 筆 + 一般查詢 50+ 筆，按簡單/中等/困難分層，人工確認正確答案，每次更新模型或 prompt 後跑回歸測試）
- [ ] Chat-to-build 能產生一個完整的進銷存模組（schema + CRUD UI）
- [ ] 至少 1 個新客戶願意付費使用 AI 建構的 ERP（而非請團隊手動開發）

## 發布計畫

- 初期：直接提供給現有接案客戶使用，由團隊負責部署和維護
- 中期：建立自助註冊 + 部署流程，客戶自行使用
- 長期：SaaS 平台，客戶線上訂閱，自動化部署

## 依賴項目

- 現有客戶的配合意願（試用 AI 查詢功能）
- LLM API 成本和延遲（影響定價和用戶體驗）
- 團隊前端開發能力（Chat-to-build 的 UI 生成）


