<?php

namespace App\Services\Ai;

use App\DataTransferObjects\Chat\ChatQueryInput;
use App\DataTransferObjects\Chat\ChatQueryResult;
use App\DataTransferObjects\Schema\SchemaContext;
use App\DataTransferObjects\Schema\TableMetadata;
use App\Enums\ChatResponseType;
use App\Enums\ConfidenceLevel;
use App\Enums\ValueFormat;
use App\Services\Schema\SchemaIntrospector;
use App\Services\Tenant\TenantQueryExecutor;
use App\Support\CurrencyFormatter;
use App\Support\NumberFormatter;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Chat-to-query 的核心 pipeline。串接 SchemaIntrospector、LlmGateway、SqlValidator、
 * TenantQueryExecutor、ConfidenceEstimator 五個零件，把使用者的自然語言問題轉成 SELECT
 * 查詢再把結果包成 ChatQueryResult 回給 controller。
 *
 * 結構：handle() 負責 schema / LLM call / 沒 function call 的 clarification fallback，
 * 再依 LLM 選到的 function name dispatch 到對應的 handleXxxQuery() method。每個 handler
 * 自己處理 args 解析、SQL validate、信心度評估、執行、結果組裝。
 *
 * 目前支援的 function：
 * - execute_query        → handleScalarQuery()  → ChatResponseType::Numeric
 * - execute_query_table  → handleTableQuery()   → ChatResponseType::Table
 *
 * 敏感欄位保護（US-7）：
 * - 第一層：system prompt 不包含 restricted 欄位，LLM 不知道它們存在
 * - 第二層：SQL 產生後檢查是否引用 restricted 欄位名，是則拒絕執行
 *
 * 錯誤處理原則：任何內部例外（LLM、validator、executor）都 catch 並轉為
 * ChatQueryResult(type=Error)，不讓例外往上傳給 controller，讓 HTTP 層保持 thin。
 */
final class QueryEngine
{
    /**
     * 表格查詢結果 row 數上限。SQL 結果超過此值會被截斷（保留前 N 筆），
     * 同時在 data.truncated 設為 true 讓前端提示使用者縮小查詢範圍。
     * 100 = 前端每頁 10 筆 × 10 頁的合理天花板。
     */
    private const int MAX_TABLE_ROWS = 100;

    /** LLM function schema 的 name——必須和 functionSchemas() 回傳的 name 欄位一致。 */
    private const string FN_EXECUTE_QUERY = 'execute_query';

    private const string FN_EXECUTE_QUERY_TABLE = 'execute_query_table';

    public function __construct(
        private readonly LlmGateway $llm,
        private readonly SchemaIntrospector $introspector,
        private readonly SqlValidator $validator,
        private readonly TenantQueryExecutor $executor,
        private readonly ConfidenceEstimator $confidenceEstimator,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(ChatQueryInput $input): ChatQueryResult
    {
        try {
            $schema = $this->introspector->introspect($input->tenantId);
        } catch (Throwable $e) {
            $this->logger->warning('QueryEngine: schema introspection failed', ['exception' => $e]);

            return $this->errorResult('無法載入資料庫結構，請聯繫管理員');
        }

        try {
            $response = $this->llm->chat(
                messages: $this->buildMessages($input, $schema),
                functions: self::functionSchemas(),
            );
        } catch (Throwable $e) {
            $this->logger->warning('QueryEngine: LLM call failed', ['exception' => $e]);

            return $this->errorResult('系統忙碌，請稍後再試');
        }

        if (! $response->hasFunctionCall()) {
            // LLM 選擇用文字回應——閒聊、拒絕寫入類、或要求釐清。
            return new ChatQueryResult(
                reply: $response->content ?? '我不太理解您的問題，可以換個方式描述嗎？',
                confidence: 0.0,
                type: ChatResponseType::Clarification,
                data: [],
                sql: null,
                tokensUsed: $response->tokensUsed,
                confidenceLevel: ConfidenceLevel::Low,
            );
        }

        // US-7 第二層防護：即使 prompt 已過濾 restricted 欄位，仍檢查 LLM 產出的 SQL
        $sql = (string) ($response->functionArguments['sql'] ?? '');
        if ($sql !== '' && $this->sqlReferencesRestrictedColumn($sql, $schema)) {
            $this->logger->warning('QueryEngine: SQL references restricted column', ['sql' => $sql]);

            return $this->errorResult('此資料受限，無法查詢');
        }

        return match ($response->functionName) {
            self::FN_EXECUTE_QUERY => $this->handleScalarQuery($response, $input),
            self::FN_EXECUTE_QUERY_TABLE => $this->handleTableQuery($response, $input),
            default => $this->unknownFunctionResult($response),
        };
    }

    /** 處理 execute_query function call，回 scalar（單一 value）結果。 */
    private function handleScalarQuery(LlmResponse $response, ChatQueryInput $input): ChatQueryResult
    {
        $args = $response->functionArguments;
        $sql = (string) ($args['sql'] ?? '');
        $replyTemplate = (string) ($args['reply_template'] ?? '');
        $baseConfidence = (float) ($args['confidence'] ?? 0.0);

        $valueFormat = ValueFormat::tryFrom((string) ($args['value_format'] ?? ''));
        if ($valueFormat === null) {
            $this->logger->warning('QueryEngine: LLM returned invalid value_format', ['args' => $args]);

            return $this->errorResult('AI 回應格式錯誤，請重試');
        }

        try {
            $this->validator->assertSelectOnly($sql);
        } catch (InvalidSqlException $e) {
            $this->logger->warning('QueryEngine: LLM produced invalid SQL', [
                'reason_code' => $e->reasonCode,
                'sql' => $sql,
            ]);

            return $this->errorResult('無法產生合法的查詢語句，請換個說法試試');
        }

        $confidence = $this->confidenceEstimator->adjust($baseConfidence, $sql);
        $level = ConfidenceLevel::fromScore($confidence);

        if ($level === ConfidenceLevel::Low) {
            return $this->lowConfidenceClarification($confidence, $sql, $response->tokensUsed);
        }

        try {
            $rows = $this->executor->execute($input->tenantId, $sql);
        } catch (Throwable $e) {
            $this->logger->warning('QueryEngine: SQL execution failed', [
                'exception' => $e,
                'sql' => $sql,
            ]);

            return $this->errorResult('查詢執行失敗，請稍後再試');
        }

        $scalar = $this->extractScalar($rows);
        if ($scalar === null) {
            return $this->errorResult('查詢沒有回傳結果');
        }

        $formatted = match ($valueFormat) {
            ValueFormat::Currency => CurrencyFormatter::ntd($scalar),
            ValueFormat::Count => NumberFormatter::thousands($scalar),
        };

        return new ChatQueryResult(
            reply: str_replace('{value}', $formatted, $replyTemplate),
            confidence: $confidence,
            type: ChatResponseType::Numeric,
            data: [
                'value' => $scalar,
                'value_format' => $valueFormat->value,
            ],
            sql: $sql,
            tokensUsed: $response->tokensUsed,
            confidenceLevel: $level,
        );
    }

    /** 處理 execute_query_table function call，回表格結果。 */
    private function handleTableQuery(LlmResponse $response, ChatQueryInput $input): ChatQueryResult
    {
        $args = $response->functionArguments;
        $sql = (string) ($args['sql'] ?? '');
        $replyTemplate = (string) ($args['reply_template'] ?? '');
        $baseConfidence = (float) ($args['confidence'] ?? 0.0);

        // LLM 可能漏填 headers、回 null、或誤填成 string——明確擋掉 wrong shape，
        // 不用 (array) 靜默轉換（會把 scalar 包成單元素 list 掩蓋真正的 bug）。
        $headers = $args['headers'] ?? null;
        if (! is_array($headers) || $headers === []) {
            $this->logger->warning('QueryEngine: LLM returned invalid headers for table query', ['args' => $args]);

            return $this->errorResult('AI 回應格式錯誤，請重試');
        }
        $headers = array_map('strval', $headers);

        try {
            $this->validator->assertSelectOnly($sql);
        } catch (InvalidSqlException $e) {
            $this->logger->warning('QueryEngine: LLM produced invalid SQL', [
                'reason_code' => $e->reasonCode,
                'sql' => $sql,
            ]);

            return $this->errorResult('無法產生合法的查詢語句，請換個說法試試');
        }

        $confidence = $this->confidenceEstimator->adjust($baseConfidence, $sql);
        $level = ConfidenceLevel::fromScore($confidence);

        if ($level === ConfidenceLevel::Low) {
            return $this->lowConfidenceClarification($confidence, $sql, $response->tokensUsed);
        }

        try {
            $rows = $this->executor->execute($input->tenantId, $sql);
        } catch (Throwable $e) {
            $this->logger->warning('QueryEngine: SQL execution failed', [
                'exception' => $e,
                'sql' => $sql,
            ]);

            return $this->errorResult('查詢執行失敗，請稍後再試');
        }

        $truncated = count($rows) > self::MAX_TABLE_ROWS;
        if ($truncated) {
            $rows = array_slice($rows, 0, self::MAX_TABLE_ROWS);
        }

        // 把 PDO 的 list of assoc array 轉成 list of list，避免前端依賴 JSON object
        // 的 key 順序保證——cell 位置用「headers 順序」對齊就好。
        $rowsAsList = array_map('array_values', $rows);

        $count = count($rowsAsList);
        $reply = $count === 0
            ? '目前沒有符合條件的資料'
            : str_replace('{count}', (string) $count, $replyTemplate);

        return new ChatQueryResult(
            reply: $reply,
            confidence: $confidence,
            type: ChatResponseType::Table,
            data: [
                'headers' => $headers,
                'rows' => $rowsAsList,
                'truncated' => $truncated,
            ],
            sql: $sql,
            tokensUsed: $response->tokensUsed,
            confidenceLevel: $level,
        );
    }

    /**
     * 低信心度（ConfidenceLevel::Low）走這條路：不執行 SQL，回 Clarification
     * 讓使用者補充細節。scalar 和 table 兩路共用同一段措辭。
     */
    private function lowConfidenceClarification(float $confidence, string $sql, int $tokensUsed): ChatQueryResult
    {
        return new ChatQueryResult(
            reply: '我不太確定要查什麼，可以提供更多細節嗎？例如具體的時間範圍或篩選條件。',
            confidence: $confidence,
            type: ChatResponseType::Clarification,
            data: [],
            sql: $sql,
            tokensUsed: $tokensUsed,
            confidenceLevel: ConfidenceLevel::Low,
        );
    }

    /**
     * LLM 回了沒註冊的 function name。代表 prompt / function schema 脫節
     * 或 LLM 產出異常，log warning 供後續校準，使用者端當結構化錯誤處理。
     */
    private function unknownFunctionResult(LlmResponse $response): ChatQueryResult
    {
        $this->logger->warning('QueryEngine: LLM called unknown function', [
            'function_name' => $response->functionName,
            'arguments' => $response->functionArguments,
        ]);

        return $this->errorResult('AI 回應格式錯誤，請重試');
    }

    /**
     * 從 SQL 結果中取出單一 scalar。典型情境是 SELECT SUM(...) / COUNT(*)
     * 回一個 row、一個 column 的查詢。回傳 null 代表查無結果或非數字。
     *
     * PHP 的 `+ 0` 會依值本身決定回 int 或 float，正確處理 MySQL decimal 的 string
     * 表示（`'999999.50'` → 999999.5 float）以及科學記號（`'1e5'` → 100000.0 float）。
     *
     * @param  list<array<string, mixed>>  $rows
     */
    private function extractScalar(array $rows): int|float|null
    {
        if ($rows === []) {
            return null;
        }

        $values = array_values($rows[0]);
        if ($values === []) {
            return null;
        }

        $firstValue = $values[0];

        if ($firstValue === null) {
            return null;
        }

        if (is_int($firstValue) || is_float($firstValue)) {
            return $firstValue;
        }

        if (is_string($firstValue) && is_numeric($firstValue)) {
            return $firstValue + 0;
        }

        return null;
    }

    /**
     * @return list<array{role: string, content: string}>
     */
    private function buildMessages(ChatQueryInput $input, SchemaContext $schema): array
    {
        return [
            ['role' => 'system', 'content' => $this->buildSystemPrompt($schema)],
            ...$input->conversationHistory,
            ['role' => 'user', 'content' => $input->message],
        ];
    }

    private function buildSystemPrompt(SchemaContext $schema): string
    {
        $lines = [
            '你是一個 ERP 查詢助手，協助使用者用自然語言查詢業務資料。',
            '',
            '可用的資料表：',
            '',
        ];

        foreach ($schema->tables as $table) {
            $lines[] = $this->formatTableForPrompt($table);
            $lines[] = '';
        }

        if ($schema->domainContext !== null) {
            $lines[] = "產業別：{$schema->domainContext}";
            $lines[] = '';
        }

        $lines[] = '## Function 選擇';
        $lines[] = '- 單一數值結果（營收、數量、平均值等）→ execute_query。SQL 必須回傳原始數字（不要用 CONCAT 或 FORMAT），後端會自動格式化';
        $lines[] = '- 多筆列表結果（逾期客戶列表、銷售排行、訂單明細等）→ execute_query_table';
        $lines[] = '';
        $lines[] = '## 信心度評分標準（confidence）';
        $lines[] = '- 0.97-1.0：問題明確、schema 完全匹配、無歧義（例：指定表的 SUM/COUNT）';
        $lines[] = '- 0.90-0.96：問題清楚但有輕微假設（例：用了 LIKE 模糊比對、或推測欄位含義）';
        $lines[] = '- 0.70-0.89：有明顯歧義或多種合理解讀（例：不確定「營收」對應哪個欄位）';
        $lines[] = '- 0.70 以下：無法確定查詢意圖，應以文字回覆要求釐清';
        $lines[] = '';
        $lines[] = '## reply_template 規則';
        $lines[] = '- execute_query：用 {value} 作為占位符。{value} 會被後端自動格式化（加 NT$ 前綴和千分位），模板裡**不要**自行加貨幣符號或單位';
        $lines[] = '- execute_query_table：用 {count} 作為資料筆數的占位符';
        $lines[] = '- 涉及時間的查詢，reply_template 說明查詢的時間範圍（例：「本月」「2026年3月」），讓使用者知道資料涵蓋哪段期間';
        $lines[] = '';
        $lines[] = '## SQL 日期處理';
        $lines[] = '- 一律用 CURDATE()、NOW() 等 MySQL 函數計算日期，不要硬編碼日期字串';
        $lines[] = '- 「本月」= DATE_FORMAT(CURDATE(), \'%Y-%m-01\') 到月底';
        $lines[] = '- 「上個月」= DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, \'%Y-%m-01\') 到本月初';
        $lines[] = '';
        $lines[] = '## execute_query_table 規則';
        $lines[] = '- headers 必須是中文欄位名稱 list，和 SQL SELECT 子句的 AS alias 完全一致';
        $lines[] = '- SQL 建議加 LIMIT 100 以內，避免回傳過多資料';
        $lines[] = '- SQL 格式化規則：金額欄位用 CONCAT(\'NT$\', FORMAT(col, 0))、日期欄位用 DATE_FORMAT(col, \'%Y-%m-%d\')、enum 狀態欄位用 CASE 翻譯成中文';
        $lines[] = '';
        $lines[] = '## 其他';
        $lines[] = '若問題需要寫入資料（新增 / 修改 / 刪除），請以文字回覆說明目前僅支援查詢。';
        $lines[] = '若問題不清楚，請以文字回覆要求使用者釐清。';

        return implode("\n", $lines);
    }

    private function formatTableForPrompt(TableMetadata $table): string
    {
        $lines = ["{$table->name}（{$table->displayName}）"];

        foreach ($table->columns as $column) {
            if ($column->restricted) {
                $lines[] = "- {$column->name}: {$column->displayName} ⛔ 敏感資料，禁止查詢。使用者問到請直接回覆「此資料受限，無法查詢」，不要產生 SQL";

                continue;
            }
            $line = "- {$column->name} ({$column->type}): {$column->displayName}";
            if ($column->description !== null) {
                $line .= " — {$column->description}";
            }
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    /**
     * OpenAI function calling 的 function schema 集合。LLM 依使用者問題選一個呼叫。
     *
     * 內容是純靜態的（schema 不會因 request 而變），但因為包含 ValueFormat::values()
     * 這種不能放進 class constant 的 runtime 呼叫，所以用 function-local static 快取
     * 首次呼叫的結果，後續請求直接回傳同一個陣列。
     *
     * @return list<array{name: string, description: string, parameters: array<string, mixed>}>
     */
    private static function functionSchemas(): array
    {
        static $schemas = null;

        return $schemas ??= [
            [
                'name' => self::FN_EXECUTE_QUERY,
                'description' => '執行 SELECT 語句回答使用者的查詢問題（單一 scalar 結果）',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'sql' => [
                            'type' => 'string',
                            'description' => '要執行的 MySQL SELECT 語句，僅限 SELECT',
                        ],
                        'reply_template' => [
                            'type' => 'string',
                            'description' => '自然語言回覆模板。{value} 會被後端格式化為含 NT$ 和千分位的字串，模板裡不要加貨幣符號。涉及時間範圍時必須包含具體日期',
                        ],
                        'value_format' => [
                            'type' => 'string',
                            'enum' => ValueFormat::values(),
                            'description' => 'currency 走 NT$ 格式，count 走千分位',
                        ],
                        'confidence' => [
                            'type' => 'number',
                            'description' => '0-1 信心度。0.97+ = 明確無歧義，0.90-0.96 = 輕微假設，0.70-0.89 = 明顯歧義',
                        ],
                    ],
                    'required' => ['sql', 'reply_template', 'value_format', 'confidence'],
                ],
            ],
            [
                'name' => self::FN_EXECUTE_QUERY_TABLE,
                'description' => '執行 SELECT 語句並以表格形式回答使用者（多筆資料列表）',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'sql' => [
                            'type' => 'string',
                            'description' => '要執行的 MySQL SELECT 語句，僅限 SELECT，建議加 LIMIT',
                        ],
                        'headers' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => '表格欄位的中文名稱 list，必須和 SQL SELECT 的 AS alias 完全一致且順序對應',
                        ],
                        'reply_template' => [
                            'type' => 'string',
                            'description' => '自然語言開場白，{count} 為資料筆數占位符。涉及時間範圍時必須包含具體日期',
                        ],
                        'confidence' => [
                            'type' => 'number',
                            'description' => '0-1 信心度。0.97+ = 明確無歧義，0.90-0.96 = 輕微假設，0.70-0.89 = 明顯歧義',
                        ],
                    ],
                    'required' => ['sql', 'headers', 'reply_template', 'confidence'],
                ],
            ],
        ];
    }

    /**
     * 檢查 SQL 是否引用了任何 restricted 欄位。用 word boundary regex 比對，
     * 避免子字串誤判（例：`restricted_col` 不應命中 `col`）。
     */
    private function sqlReferencesRestrictedColumn(string $sql, SchemaContext $schema): bool
    {
        $restrictedNames = [];
        foreach ($schema->tables as $table) {
            foreach ($table->columns as $column) {
                if ($column->restricted) {
                    $restrictedNames[] = preg_quote($column->name, '/');
                }
            }
        }

        if ($restrictedNames === []) {
            return false;
        }

        $pattern = '/\b('.implode('|', $restrictedNames).')\b/i';

        return preg_match($pattern, $sql) === 1;
    }

    private function errorResult(string $reply): ChatQueryResult
    {
        return new ChatQueryResult(
            reply: $reply,
            confidence: 0.0,
            type: ChatResponseType::Error,
            data: [],
            confidenceLevel: ConfidenceLevel::Low,
        );
    }
}
