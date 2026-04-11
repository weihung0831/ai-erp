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

## 安全性與合規

- **認證：** Laravel Sanctum（API token）。OAuth2 + JWT 規劃於未來開放第三方整合時再導入
- **資料隔離：** 多租戶間嚴格隔離，任何查詢只能存取該租戶的資料
- **加密：** 資料庫連線 TLS，敏感欄位（薪資、成本）加密儲存
- **個資法合規：** 符合台灣個人資料保護法，提供資料匯出/刪除功能
- **AI 安全：** LLM 生成的 SQL 只允許 SELECT（read-only connection），禁止 DDL/DML 操作於 query 模組

## 方案

接入現有客戶的 ERP 系統，提供 AI 聊天介面進行查詢與建構。過去接案累積的 pattern 抽象為 domain knowledge layer 作為長期護城河。Chat-to-build 產生的 schema 由 AI 自己設計，Chat-to-query 查詢同一 schema 可達更高準確率，形成正向循環。

### 路線圖

每個 Phase 皆由「元件庫（[00](../spec/00-component-library.md)）→ 後端 spec → 前端 spec」三個文件共同定義，後端回傳 JSON、前端透過 Blade + Alpine.js + Axios 呼叫。

**Phase 1 — Chat-to-query（MVP）：** 在現有客戶的 ERP 系統上加聊天介面，以自然語言查詢業務資料。
- **後端：** 聊天 API、自然語言轉 SQL、信心度分層回退、認證與租戶隔離、對話歷史、查詢日誌、敏感欄位保護。品質閘門：Golden Test Suite（100 筆關鍵財務 + 50 筆一般查詢），準確率達標（關鍵財務 > 99%、一般 > 95%），查詢回應 < 5 秒。詳見 [01-phase1-backend.md](../spec/01-phase1-backend.md)。
- **前端：** 登入 / 忘記密碼 / 重設密碼頁、聊天主頁、查詢日誌頁（admin）、快捷按鈕管理頁（admin）、敏感欄位管理頁（admin）。詳見 [02-phase1-frontend.md](../spec/02-phase1-frontend.md)。

**查詢回退策略（信心度分層）：**
- **高信心（> 95%）：** 直接回答，背景記錄 SQL 和結果供審計
- **中信心（70-95%）：** 回答但附提示「此查詢較複雜，建議確認」，可展開看 SQL
- **低信心（< 70%）：** 不回答，改為引導使用者釐清意圖（「你是要查 A 還是 B？」）

核心原則：寧可不回答，也不回答錯的數字。

**Phase 2 — Chat-to-build：** 客戶透過對話描述業務需求，AI 產生 MySQL schema + Laravel Model/Controller + Blade CRUD UI（template scaffold，非 free-form）。
- **後端：** 對話式需求收集、產業識別與 domain knowledge 匹配、schema + Model/Controller + Blade CRUD 生成 pipeline、預覽與確認機制、Schema 版本管理（可回退）。品質閘門：Domain Knowledge 至少涵蓋 3 個產業、Build Test Suite。不包含自訂商業邏輯、第三方系統串接、報表設計器。詳見 [03-phase2-backend.md](../spec/03-phase2-backend.md)。
- **前端：** 建構聊天頁、Schema 預覽頁、動態 CRUD 列表 / 表單頁、Schema 版本管理頁（admin）。詳見 [04-phase2-frontend.md](../spec/04-phase2-frontend.md)。

**Phase 3 — SaaS 轉型與知識回饋循環：** 從接案服務轉型為 SaaS 平台。
- **後端：**
    - 知識回饋：客戶 schema 經人工審核後回饋到 domain knowledge；定期跑 golden test + build test 追蹤準確率趨勢
    - 自助註冊：Email 驗證、reCAPTCHA、租戶 DB 自動建立
    - 訂閱與付費：月繳 / 年繳訂閱制（四層級方案）、台灣在地金流（綠界 / 藍新）、電子發票加值中心串接、用量計費
    - 平台管理：營運儀表板 API、租戶管理（狀態機 active → grace → suspended → archived → deleted）、資料匯出
    - 詳見 [05-phase3-backend.md](../spec/05-phase3-backend.md)。
- **前端：**
    - 公開頁：產品首頁、註冊頁、Email 驗證結果頁、方案選擇頁
    - Onboarding：產業選擇 → 模組推薦 → AI 建構 → 第一筆資料引導 → Chat-to-query 示範
    - 客戶後台：用量儀表板、訂閱管理（含付款方式）、發票查詢
    - 平台管理後台：營運儀表板、租戶管理、Domain Knowledge 管理、準確率趨勢
    - 詳見 [06-phase3-frontend.md](../spec/06-phase3-frontend.md)。

### 技術方向

| 層級 | 技術選擇 | 說明 |
|------|----------|------|
| Backend | PHP + Laravel | 團隊現有技術棧 |
| Database | MySQL | 團隊熟悉，現有客戶系統一致 |
| AI Layer | OpenAI GPT-4o + function calling | Text-to-SQL 準確率最高，PHP SDK 完整 |
| Frontend | Blade + Alpine.js + Axios | Laravel 原生模板，Alpine.js 管理聊天狀態，Axios 處理 API 請求 |
| UI 設計 | Claude DESIGN.md | 溫暖聊天介面風格，適合非技術用戶 |
| 多租戶 | DB-per-tenant | 每個客戶獨立 MySQL DB |

### 多租戶策略

採用 DB-per-tenant：每個客戶獨立 MySQL DB，完全隔離、獨立備份/還原。Chat-to-build 為每個客戶產生不同 schema，獨立 DB 避免租戶間衝突。

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


