<?php

namespace App\Services\Ai;

use App\DataTransferObjects\Chat\ChatQueryInput;
use App\DataTransferObjects\Chat\ChatQueryResult;
use App\DataTransferObjects\Schema\SchemaContext;
use App\DataTransferObjects\Schema\TableMetadata;
use App\Enums\ChatResponseType;
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
 * US-1 階段只處理「單一 scalar 回答」（type=Numeric），幣別與計數兩種格式。
 * 表格查詢（US-2）、多輪對話（US-3）、SQL preview 呈現（US-4）、敏感欄位
 * （US-7）都不在這一輪範圍內。
 *
 * 錯誤處理原則：任何內部例外（LLM、validator、executor）都 catch 並轉為
 * ChatQueryResult(type=Error)，不讓例外往上傳給 controller，讓 HTTP 層保持 thin。
 */
final class QueryEngine
{
    /** 信心度閾值：低於此值不執行 SQL，改為回 Clarification。 */
    private const float LOW_CONFIDENCE_THRESHOLD = 0.70;

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
                functions: self::executeQueryFunctionSchema(),
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
            );
        }

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

        if ($confidence < self::LOW_CONFIDENCE_THRESHOLD) {
            return new ChatQueryResult(
                reply: '我不太確定要查什麼，可以提供更多細節嗎？例如具體的時間範圍或篩選條件。',
                confidence: $confidence,
                type: ChatResponseType::Clarification,
                data: [],
                sql: $sql,
                tokensUsed: $response->tokensUsed,
            );
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
        );
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

        $lines[] = '請呼叫 execute_query function 產生 MySQL SELECT 語句回答使用者問題。';
        $lines[] = '若問題需要寫入資料（新增 / 修改 / 刪除），請以文字回覆說明目前僅支援查詢。';
        $lines[] = '若問題不清楚，請以文字回覆要求使用者釐清。';

        return implode("\n", $lines);
    }

    private function formatTableForPrompt(TableMetadata $table): string
    {
        $lines = ["{$table->name}（{$table->displayName}）"];

        foreach ($table->columns as $column) {
            $line = "- {$column->name} ({$column->type}): {$column->displayName}";
            if ($column->description !== null) {
                $line .= " — {$column->description}";
            }
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    /**
     * OpenAI function calling 的 execute_query 函式 schema。
     *
     * 內容是純靜態的（schema 不會因 request 而變），但因為包含 ValueFormat::values()
     * 這種不能放進 class constant 的 runtime 呼叫，所以用 function-local static 快取
     * 首次呼叫的結果，後續請求直接回傳同一個陣列。
     *
     * @return list<array{name: string, description: string, parameters: array<string, mixed>}>
     */
    private static function executeQueryFunctionSchema(): array
    {
        static $schema = null;

        return $schema ??= [
            [
                'name' => 'execute_query',
                'description' => '執行 SELECT 語句回答使用者的查詢問題',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'sql' => [
                            'type' => 'string',
                            'description' => '要執行的 MySQL SELECT 語句，僅限 SELECT',
                        ],
                        'reply_template' => [
                            'type' => 'string',
                            'description' => '自然語言回覆模板，使用 {value} 作為查詢結果的占位符',
                        ],
                        'value_format' => [
                            'type' => 'string',
                            'enum' => ValueFormat::values(),
                            'description' => 'currency 走 NT$ 格式，count 走千分位',
                        ],
                        'confidence' => [
                            'type' => 'number',
                            'description' => '0-1 之間的信心度分數',
                        ],
                    ],
                    'required' => ['sql', 'reply_template', 'value_format', 'confidence'],
                ],
            ],
        ];
    }

    private function errorResult(string $reply): ChatQueryResult
    {
        return new ChatQueryResult(
            reply: $reply,
            confidence: 0.0,
            type: ChatResponseType::Error,
            data: [],
        );
    }
}
