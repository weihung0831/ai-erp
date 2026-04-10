# 設計模式規範

日期：2026-04-11
狀態：已核准
依據：[架構文件](../architecture/system-architecture.md)

## 總覽

本專案採用的設計模式，確保程式碼一致性和可維護性。

## 後端設計模式

> **注意：** Web Controller（`Controllers/Web/`）只負責回傳 Blade view，不呼叫 Service 也不操作資料庫。以下 pattern 僅適用於 API Controller（`Controllers/Api/`）和 Service 層。

### 1. Repository Pattern

資料存取邏輯封裝在 Repository 中，Controller 和 Service 不直接操作 Eloquent Model。

**結構：**
```
app/Repositories/
├── Contracts/                    # Interface
│   ├── TenantRepositoryInterface.php
│   ├── ChatHistoryRepositoryInterface.php
│   └── UserRepositoryInterface.php
└── Eloquent/                     # 實作
    ├── TenantRepository.php
    ├── ChatHistoryRepository.php
    └── UserRepository.php
```

**規則：**
- 每個 Model 對應一個 Repository Interface + Eloquent 實作
- Repository 只負責 CRUD 和查詢，不含業務邏輯
- 透過 Laravel Service Provider 綁定 Interface → 實作
- 方便未來抽換資料來源（例如從 MySQL 換到其他 DB）

**範例：**
```php
// Interface
interface ChatHistoryRepositoryInterface
{
    public function findByConversation(string $conversationId): Collection;
    public function create(array $data): ChatHistory;
    public function getRecentByTenant(int $tenantId, int $limit = 50): Collection;
}

// 實作
class ChatHistoryRepository implements ChatHistoryRepositoryInterface
{
    public function __construct(private ChatHistory $model) {}

    public function findByConversation(string $conversationId): Collection
    {
        return $this->model->where('conversation_id', $conversationId)
            ->orderBy('created_at')
            ->get();
    }
}
```

### 2. Service Pattern

業務邏輯封裝在 Service 中，Controller 保持精簡（thin controller）。

**結構：**
```
app/Services/
├── Ai/
│   ├── LlmGateway.php             # Interface
│   ├── OpenAiGateway.php           # 實作
│   ├── QueryEngine.php
│   ├── BuildEngine.php
│   ├── ConfidenceEstimator.php
│   └── SqlValidator.php
├── Tenant/
│   └── TenantManager.php
└── Schema/
    └── SchemaIntrospector.php
```

**規則：**
- Controller 只做：接收 request → 呼叫 Service → 回傳 response
- Service 負責組合 Repository 和其他 Service 完成業務邏輯
- Service 之間可以互相呼叫，但避免循環依賴
- 每個 Service 透過 constructor injection 注入依賴

**範例：**
```php
// Controller（精簡）
class ChatController extends Controller
{
    public function __construct(private QueryEngine $queryEngine) {}

    public function chat(ChatRequest $request): JsonResponse
    {
        $result = $this->queryEngine->process($request->validated());
        return response()->json($result);
    }
}

// Service（業務邏輯）
class QueryEngine
{
    public function __construct(
        private LlmGateway $llm,
        private SchemaIntrospector $schema,
        private SqlValidator $validator,
        private ConfidenceEstimator $confidence,
        private ChatHistoryRepositoryInterface $chatHistory,
    ) {}

    public function process(array $input): array
    {
        // 1. 載入 schema
        // 2. 呼叫 LLM
        // 3. 驗證 SQL
        // 4. 評估信心度
        // 5. 執行並格式化
    }
}
```

### 3. Factory Pattern

物件的建立統一透過 Factory，集中管理實例化邏輯。

**使用場景：**

| Factory | 職責 |
|---------|------|
| `LlmGatewayFactory` | 根據設定建立對應的 LLM 實例（OpenAI / Claude / 其他） |
| `TenantDatabaseFactory` | 建立新租戶的 DB、MySQL user、初始 schema |
| `MigrationFactory` | 根據 Schema JSON 產生 Laravel migration 檔案 |
| `CrudScaffoldFactory` | 根據 Schema JSON 產生 Model / Controller / Blade 模板 |

**範例：**
```php
// 透過 Service Provider 綁定，不使用 static factory
// AppServiceProvider.php
$this->app->bind(LlmGateway::class, function ($app) {
    return match (config('ai.default_provider')) {
        'openai' => $app->make(OpenAiGateway::class),
        'claude' => $app->make(ClaudeGateway::class),
        default => throw new InvalidArgumentException('Unknown LLM provider'),
    };
});
```

**規則：** Factory 統一透過 Service Provider 綁定，不使用 static method，與 constructor injection 規則一致。

### 4. Strategy Pattern

同一介面的不同實作，根據情境切換策略。

**使用場景：**

| Interface | 策略 | 說明 |
|-----------|------|------|
| `LlmGateway` | `OpenAiGateway` / `ClaudeGateway` | 不同 LLM 供應商 |
| `ResponseFormatter` | `NumericFormatter` / `TableFormatter` / `SummaryFormatter` | 不同查詢結果的格式化方式 |
| `ConfidenceStrategy` | `SchemaMatchStrategy` / `LlmSelfEvalStrategy` | 不同信心度評估方法 |

**範例：**
```php
interface ResponseFormatter
{
    public function format(array $queryResult, array $metadata): array;
}

class NumericFormatter implements ResponseFormatter
{
    public function format(array $queryResult, array $metadata): array
    {
        return [
            'type' => 'numeric',
            'reply' => "本月營收為 NT$" . number_format($queryResult[0]['total']),
            'data' => $queryResult[0],
        ];
    }
}

class TableFormatter implements ResponseFormatter
{
    public function format(array $queryResult, array $metadata): array
    {
        return [
            'type' => 'table',
            'reply' => "查詢結果如下：",
            'data' => ['headers' => $metadata['columns'], 'rows' => $queryResult],
        ];
    }
}
```

### 5. Middleware Pattern

請求管線中的橫切關注點（認證、租戶切換）封裝在 Middleware 中。

**使用場景：**

| Middleware | 職責 | 適用路由 |
|------------|------|----------|
| `Authenticate` | Laravel Sanctum token 驗證 | `api/*`（除 login/register） |
| `TenantMiddleware` | 解析 tenant_id，切換 DB 連線 | `api/*`（除 login/register） |
| `AdminMiddleware` | 檢查 user role = admin | `api/admin/*`、`api/build/confirm` |

**規則：**
- Middleware 只做檢查和設定，不含業務邏輯
- Middleware 順序：Authenticate → TenantMiddleware → AdminMiddleware
- 失敗時回傳標準 JSON 錯誤格式

### 6. Cache Pattern

LLM 回應快取，避免重複查詢浪費 token。

**策略：**
```php
class QueryCache
{
    public function getOrQuery(string $tenantId, string $message, callable $queryFn): array
    {
        $key = "query:{$tenantId}:" . md5($message);
        return Cache::remember($key, now()->addMinutes(30), $queryFn);
    }

    public function invalidate(string $tenantId): void
    {
        // 租戶資料變更時清除該租戶所有快取
        Cache::tags("tenant:{$tenantId}")->flush();
    }
}
```

**規則：**
- 需要支援 tag 的 cache driver（Redis 或 Memcached），不可用 file driver
- 快取 key 包含 tenant_id，確保跨租戶隔離
- Schema 變更（Chat-to-build）時清除該租戶的查詢快取
- TTL 預設 30 分鐘，可依查詢類型調整

### 7. Retry Pattern

外部 API 呼叫（LLM）的重試和降級機制。

**規則：**
- 重試次數依場景決定，各 spec 自行指定：
  - Chat-to-query（Phase 1 即時互動）：**不重試**，timeout 直接回錯，避免使用者等待
  - Chat-to-build（Phase 2 非即時）：**重試 1 次**
  - 租戶 DB 建立（Phase 3 背景任務）：**重試 2 次**
- Timeout：LLM 10 秒、SQL 3 秒
- 所有重試都記錄到 log
- 連續失敗不重試，直接回傳錯誤

```php
class RetryHandler
{
    public function attempt(callable $fn, int $maxRetries = 2, int $delayMs = 1000): mixed
    {
        $lastException = null;
        for ($i = 0; $i <= $maxRetries; $i++) {
            try {
                return $fn();
            } catch (Throwable $e) {
                $lastException = $e;
                Log::warning("Retry {$i}/{$maxRetries}", ['error' => $e->getMessage()]);
                if ($i < $maxRetries) usleep($delayMs * 1000);
            }
        }
        throw $lastException;
    }
}
```

### 8. Observer Pattern（Laravel Events）

> 注意：Observer Pattern 後續的 DTO 和前端 Pattern 接續編號為 9、10、11。

使用 Laravel Event/Listener 解耦副作用邏輯。

**使用場景：**

| Event | Listener | 說明 |
|-------|----------|------|
| `QueryExecuted` | `LogQueryListener` | 記錄查詢日誌 |
| `QueryExecuted` | `TrackTokenUsageListener` | 追蹤 token 用量 |
| `SchemaBuilt` | `UpdateSchemaMetadataListener` | 更新 schema metadata |
| `SchemaBuilt` | `RecordVersionListener` | 記錄 schema 版本 |
| `TenantCreated` | `CreateTenantDatabaseListener` | 建立租戶 DB |
| `PaymentFailed` | `SendPaymentReminderListener` | 發送付款提醒 |

**規則：**
- 主流程中不直接做副作用（寫 log、發通知、更新統計）
- 副作用透過 Event 觸發 Listener 處理
- Listener 可以設為 queue job 非同步執行

### 9. DTO（Data Transfer Object）

API 請求和回應使用 DTO，不直接傳遞 array。

DTO 與 Laravel FormRequest 的分工：
- **FormRequest** — 驗證 HTTP 請求參數（`ChatFormRequest extends FormRequest`）
- **DTO** — Service 層之間傳遞的資料物件（`ChatResponseDto`），不處理驗證

**結構：**
```
app/Http/Requests/              # FormRequest（驗證層）
├── ChatFormRequest.php
└── BuildFormRequest.php
app/DTOs/                       # DTO（資料傳遞）
├── ChatResponseDto.php
├── BuildPreviewDto.php
└── SchemaDefinitionDto.php
```

**範例：**
```php
class ChatResponseDto
{
    public function __construct(
        public readonly string $reply,
        public readonly float $confidence,
        public readonly string $type,
        public readonly array $data,
        public readonly ?string $sql,
        public readonly int $tokensUsed,
    ) {}

    public function toArray(): array
    {
        return [
            'reply' => $this->reply,
            'confidence' => $this->confidence,
            'type' => $this->type,
            'data' => $this->data,
            'sql_preview' => $this->confidence < 0.95 ? $this->sql : null,
            'tokens_used' => $this->tokensUsed,
        ];
    }
}
```

## 前端設計模式

### 10. Component Pattern（Blade 元件化）

所有 UI 以 Blade Component 封裝，像 Vue component 一樣可重複使用。

**結構：**
```
resources/views/components/
├── chat/
│   ├── bubble.blade.php          <x-chat.bubble>
│   ├── input.blade.php           <x-chat.input>
│   ├── quick-actions.blade.php   <x-chat.quick-actions>
│   └── confidence.blade.php      <x-chat.confidence>
├── data/
│   ├── table.blade.php           <x-data.table>
│   ├── stat-card.blade.php       <x-data.stat-card>
│   └── pagination.blade.php      <x-data.pagination>
├── form/
│   ├── input.blade.php           <x-form.input>
│   ├── select.blade.php          <x-form.select>
│   ├── toggle.blade.php          <x-form.toggle>
│   └── date-picker.blade.php     <x-form.date-picker>
├── layout/
│   ├── sidebar.blade.php         <x-layout.sidebar>
│   ├── header.blade.php          <x-layout.header>
│   └── page.blade.php            <x-layout.page>
└── ui/
    ├── button.blade.php          <x-ui.button>
    ├── badge.blade.php           <x-ui.badge>
    ├── modal.blade.php           <x-ui.modal>
    └── alert.blade.php           <x-ui.alert>
```

**規則：**
- 每個 Blade Component 對應一個獨立檔案
- Component 接受 props（`@props`），不依賴全域狀態
- 樣式內聯在 component 中或引用共用 CSS class
- 複雜互動邏輯用 Alpine.js `x-data` 封裝在 component 內

**範例：**
```blade
<!-- resources/views/components/chat/bubble.blade.php -->
@props(['type' => 'ai', 'confidence' => null])

<div x-data="{ showSql: false }"
     class="chat-bubble chat-bubble--{{ $type }}">
    <div class="chat-bubble__content">
        {{ $slot }}
    </div>

    @if($confidence && $confidence < 0.95)
        <x-chat.confidence :score="$confidence" />
        <button @click="showSql = !showSql" class="chat-bubble__sql-toggle">
            顯示 SQL
        </button>
        <div x-show="showSql" class="chat-bubble__sql">
            {{ $sql ?? '' }}
        </div>
    @endif
</div>
```

### 11. Store Pattern（Alpine.js 狀態管理）

頁面級的狀態用 Alpine.js `Alpine.store()` 集中管理，避免 props drilling。

**使用場景：**

| Store | 管理什麼 |
|-------|----------|
| `chatStore` | 對話歷史、目前輸入、載入狀態、conversation_id |
| `authStore` | 登入狀態、token、使用者資訊、租戶資訊 |
| `buildStore` | 建構對話狀態、預覽資料、確認流程 |
| `sidebarStore` | 動態導覽列項目、目前選取頁面 |

**範例：**
```javascript
// resources/js/stores/chat.js
document.addEventListener('alpine:init', () => {
    Alpine.store('chat', {
        messages: [],
        input: '',
        loading: false,
        conversationId: null,

        async send() {
            if (!this.input.trim()) return;

            const message = this.input;
            this.messages.push({ type: 'user', content: message });
            this.input = '';
            this.loading = true;

            try {
                const response = await axios.post('/api/chat', {
                    message,
                    conversation_id: this.conversationId,
                });
                this.messages.push({
                    type: 'ai',
                    content: response.data.reply,
                    confidence: response.data.confidence,
                    data: response.data.data,
                    sql: response.data.sql_preview,
                });
                this.conversationId = response.data.conversation_id;
            } catch (error) {
                this.messages.push({ type: 'error', content: '系統忙碌，請稍後再試' });
            } finally {
                this.loading = false;
            }
        },

        newConversation() {
            this.messages = [];
            this.conversationId = null;
        },
    });
});
```

## 共用原則

### 命名規範

| 類型 | 規範 | 範例 |
|------|------|------|
| Controller | PascalCase + Controller 後綴 | `ChatController` |
| Service | PascalCase | `QueryEngine` |
| Repository | PascalCase + Repository 後綴 | `ChatHistoryRepository` |
| DTO | PascalCase | `ChatResponse` |
| Event | PascalCase，過去式 | `QueryExecuted` |
| Listener | PascalCase + Listener 後綴 | `LogQueryListener` |
| Factory | PascalCase + Factory 後綴 | `LlmGatewayFactory` |
| Blade Component | kebab-case，點分隔命名空間 | `<x-chat.bubble>` |
| Alpine Store | camelCase | `chatStore` |
| API 路由 | kebab-case | `/api/chat`, `/api/build/confirm` |
| DB 欄位 | snake_case | `created_at`, `tenant_id` |

### 依賴注入規則

- 所有 Service、Repository 透過 constructor injection
- 不使用 facade（`DB::`, `Auth::` 等）在 Service 層，改用注入
- Controller 可以使用 facade（Laravel 慣例）
- 綁定在 `AppServiceProvider` 或專用 Provider 中
