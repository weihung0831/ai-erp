<?php

namespace Tests\Unit\Services\Ai;

use App\DataTransferObjects\Chat\ChatQueryInput;
use App\Enums\ChatResponseType;
use App\Services\Ai\ConfidenceEstimator;
use App\Services\Ai\LlmResponse;
use App\Services\Ai\QueryEngine;
use App\Services\Ai\SqlValidator;
use App\Services\Schema\SchemaIntrospector;
use Illuminate\Config\Repository;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use Tests\Fakes\FakeLlmGateway;
use Tests\Fakes\FakeTenantQueryExecutor;

final class QueryEngineTest extends TestCase
{
    private FakeLlmGateway $llm;

    private FakeTenantQueryExecutor $executor;

    private QueryEngine $engine;

    protected function setUp(): void
    {
        $this->llm = new FakeLlmGateway;
        $this->executor = new FakeTenantQueryExecutor;
        $this->engine = $this->makeEngine($this->llm, $this->executor);
    }

    public function test_happy_path_currency_query_returns_formatted_reply(): void
    {
        $this->llm->queueResponse(new LlmResponse(
            functionName: 'execute_query',
            functionArguments: [
                'sql' => 'SELECT SUM(total_amount) AS total FROM orders',
                'reply_template' => '本月營收為 {value}',
                'value_format' => 'currency',
                'confidence' => 0.97,
            ],
            content: null,
            tokensUsed: 1847,
        ));
        $this->executor->queueResult([['total' => 1234567]]);

        $result = $this->engine->handle(new ChatQueryInput(
            message: '這個月營收多少',
            tenantId: 1,
        ));

        $this->assertSame(ChatResponseType::Numeric, $result->type);
        $this->assertSame('本月營收為 NT$1,234,567', $result->reply);
        $this->assertSame(0.97, $result->confidence);
        $this->assertSame(1234567, $result->data['value']);
        $this->assertSame('currency', $result->data['value_format']);
        $this->assertSame('SELECT SUM(total_amount) AS total FROM orders', $result->sql);
        $this->assertSame(1847, $result->tokensUsed);
        $this->assertSame(1, $this->executor->callCount());
    }

    public function test_happy_path_count_query_uses_thousands_formatter(): void
    {
        $this->llm->queueResponse(new LlmResponse(
            functionName: 'execute_query',
            functionArguments: [
                'sql' => 'SELECT COUNT(*) AS cnt FROM customers',
                'reply_template' => '目前有 {value} 位客戶',
                'value_format' => 'count',
                'confidence' => 0.98,
            ],
            content: null,
            tokensUsed: 500,
        ));
        $this->executor->queueResult([['cnt' => 12345]]);

        $result = $this->engine->handle(new ChatQueryInput(
            message: '目前有多少客戶',
            tenantId: 1,
        ));

        $this->assertSame(ChatResponseType::Numeric, $result->type);
        $this->assertSame('目前有 12,345 位客戶', $result->reply);
        $this->assertSame('count', $result->data['value_format']);
    }

    public function test_casts_string_decimal_from_mysql_to_float(): void
    {
        // MySQL DECIMAL 欄位會以 string 形式透過 PDO 回來
        $this->llm->queueResponse(new LlmResponse(
            functionName: 'execute_query',
            functionArguments: [
                'sql' => 'SELECT SUM(total_amount) AS total FROM orders',
                'reply_template' => '合計 {value}',
                'value_format' => 'currency',
                'confidence' => 0.96,
            ],
            content: null,
            tokensUsed: 100,
        ));
        $this->executor->queueResult([['total' => '999999.50']]);

        $result = $this->engine->handle(new ChatQueryInput(message: '總營收', tenantId: 1));

        $this->assertSame(ChatResponseType::Numeric, $result->type);
        $this->assertSame('合計 NT$1,000,000', $result->reply);
    }

    public function test_invalid_value_format_from_llm_returns_error(): void
    {
        $this->llm->queueResponse(new LlmResponse(
            functionName: 'execute_query',
            functionArguments: [
                'sql' => 'SELECT 1',
                'reply_template' => '{value}',
                'value_format' => 'bogus',
                'confidence' => 0.99,
            ],
            content: null,
            tokensUsed: 100,
        ));

        $result = $this->engine->handle(new ChatQueryInput(message: 'x', tenantId: 1));

        $this->assertSame(ChatResponseType::Error, $result->type);
        $this->assertSame('AI 回應格式錯誤，請重試', $result->reply);
        $this->assertSame(0, $this->executor->callCount(), '格式錯誤不該執行 SQL');
    }

    public function test_plain_text_response_without_function_call_returns_clarification(): void
    {
        $this->llm->queueResponse(new LlmResponse(
            functionName: null,
            functionArguments: [],
            content: '您好！我可以協助您查詢業務資料，請試著問我具體的問題。',
            tokensUsed: 80,
        ));

        $result = $this->engine->handle(new ChatQueryInput(message: '你好', tenantId: 1));

        $this->assertSame(ChatResponseType::Clarification, $result->type);
        $this->assertSame('您好！我可以協助您查詢業務資料，請試著問我具體的問題。', $result->reply);
        $this->assertSame(0, $this->executor->callCount(), 'Clarification 不應執行 SQL');
    }

    public function test_llm_gateway_exception_returns_error_result(): void
    {
        $this->llm->shouldFailWith(new RuntimeException('timeout'));

        $result = $this->engine->handle(new ChatQueryInput(message: '營收', tenantId: 1));

        $this->assertSame(ChatResponseType::Error, $result->type);
        $this->assertSame('系統忙碌，請稍後再試', $result->reply);
        $this->assertSame(0, $this->executor->callCount());
    }

    public function test_invalid_sql_from_llm_returns_error_result(): void
    {
        $this->llm->queueResponse(new LlmResponse(
            functionName: 'execute_query',
            functionArguments: [
                'sql' => 'DROP TABLE orders',  // 不是 SELECT，SqlValidator 會擋
                'reply_template' => '{value}',
                'value_format' => 'count',
                'confidence' => 0.99,
            ],
            content: null,
            tokensUsed: 100,
        ));

        $result = $this->engine->handle(new ChatQueryInput(message: '...', tenantId: 1));

        $this->assertSame(ChatResponseType::Error, $result->type);
        $this->assertStringContainsString('無法產生合法的查詢語句', $result->reply);
        $this->assertSame(0, $this->executor->callCount());
    }

    public function test_executor_exception_returns_error_result(): void
    {
        $this->llm->queueResponse(new LlmResponse(
            functionName: 'execute_query',
            functionArguments: [
                'sql' => 'SELECT SUM(total_amount) FROM orders',
                'reply_template' => '{value}',
                'value_format' => 'currency',
                'confidence' => 0.97,
            ],
            content: null,
            tokensUsed: 100,
        ));
        $this->executor->shouldFailWith(new RuntimeException('connection lost'));

        $result = $this->engine->handle(new ChatQueryInput(message: '營收', tenantId: 1));

        $this->assertSame(ChatResponseType::Error, $result->type);
        $this->assertSame('查詢執行失敗，請稍後再試', $result->reply);
    }

    public function test_empty_sql_result_returns_error_result(): void
    {
        $this->llm->queueResponse(new LlmResponse(
            functionName: 'execute_query',
            functionArguments: [
                'sql' => 'SELECT SUM(total_amount) FROM orders WHERE 1 = 0',
                'reply_template' => '{value}',
                'value_format' => 'currency',
                'confidence' => 0.97,
            ],
            content: null,
            tokensUsed: 100,
        ));
        $this->executor->queueResult([]);

        $result = $this->engine->handle(new ChatQueryInput(message: '營收', tenantId: 1));

        $this->assertSame(ChatResponseType::Error, $result->type);
        $this->assertSame('查詢沒有回傳結果', $result->reply);
    }

    public function test_low_confidence_skips_sql_execution_and_returns_clarification(): void
    {
        $this->llm->queueResponse(new LlmResponse(
            functionName: 'execute_query',
            functionArguments: [
                'sql' => 'SELECT SUM(total_amount) FROM orders',
                'reply_template' => '營收 {value}',
                'value_format' => 'currency',
                'confidence' => 0.50,  // 低於 0.70 門檻
            ],
            content: null,
            tokensUsed: 100,
        ));

        $result = $this->engine->handle(new ChatQueryInput(message: '???', tenantId: 1));

        $this->assertSame(ChatResponseType::Clarification, $result->type);
        $this->assertStringContainsString('不太確定', $result->reply);
        $this->assertSame(0.50, $result->confidence);
        $this->assertSame(0, $this->executor->callCount(), '低信心度不該執行 SQL');
    }

    public function test_mid_confidence_still_executes_and_returns_numeric(): void
    {
        $this->llm->queueResponse(new LlmResponse(
            functionName: 'execute_query',
            functionArguments: [
                'sql' => 'SELECT SUM(total_amount) FROM orders',
                'reply_template' => '營收 {value}',
                'value_format' => 'currency',
                'confidence' => 0.80,  // 落在 mid（0.70-0.95）
            ],
            content: null,
            tokensUsed: 100,
        ));
        $this->executor->queueResult([['total' => 500000]]);

        $result = $this->engine->handle(new ChatQueryInput(message: '營收', tenantId: 1));

        $this->assertSame(ChatResponseType::Numeric, $result->type);
        $this->assertSame('營收 NT$500,000', $result->reply);
        $this->assertSame(0.80, $result->confidence);
        $this->assertSame(1, $this->executor->callCount());
    }

    public function test_like_penalty_can_drop_confidence_below_threshold(): void
    {
        // base 0.75 - 0.1 (LIKE) = 0.65 < 0.70
        $this->llm->queueResponse(new LlmResponse(
            functionName: 'execute_query',
            functionArguments: [
                'sql' => 'SELECT COUNT(*) FROM customers WHERE name LIKE "%王%"',
                'reply_template' => '{value} 位',
                'value_format' => 'count',
                'confidence' => 0.75,
            ],
            content: null,
            tokensUsed: 100,
        ));

        $result = $this->engine->handle(new ChatQueryInput(message: '姓王的客戶幾個', tenantId: 1));

        $this->assertSame(ChatResponseType::Clarification, $result->type);
        $this->assertEqualsWithDelta(0.65, $result->confidence, 0.0001);
        $this->assertSame(0, $this->executor->callCount());
    }

    public function test_system_prompt_contains_schema_tables_and_function_definition(): void
    {
        $this->llm->queueResponse(new LlmResponse(
            functionName: null,
            functionArguments: [],
            content: '...',
            tokensUsed: 0,
        ));

        $this->engine->handle(new ChatQueryInput(message: '隨便問', tenantId: 1));

        $call = $this->llm->calls[0];
        $systemMessage = $call['messages'][0]['content'];
        $userMessage = $call['messages'][1]['content'];

        $this->assertStringContainsString('orders', $systemMessage);
        $this->assertStringContainsString('訂單', $systemMessage);
        $this->assertStringContainsString('total_amount', $systemMessage);
        $this->assertStringContainsString('customers', $systemMessage);
        $this->assertStringContainsString('餐飲業', $systemMessage);
        $this->assertSame('隨便問', $userMessage);

        $this->assertCount(1, $call['functions']);
        $this->assertSame('execute_query', $call['functions'][0]['name']);
        $this->assertContains('sql', $call['functions'][0]['parameters']['required']);
    }

    private function makeEngine(FakeLlmGateway $llm, FakeTenantQueryExecutor $executor): QueryEngine
    {
        $config = new Repository([
            'schema_fixtures' => [
                'tenants' => [
                    1 => [
                        'domain_context' => '餐飲業',
                        'tables' => [
                            [
                                'name' => 'orders',
                                'display_name' => '訂單',
                                'columns' => [
                                    ['name' => 'id', 'type' => 'int', 'display_name' => '訂單編號'],
                                    ['name' => 'total_amount', 'type' => 'decimal', 'display_name' => '訂單金額'],
                                    ['name' => 'created_at', 'type' => 'datetime', 'display_name' => '建立時間'],
                                ],
                            ],
                            [
                                'name' => 'customers',
                                'display_name' => '客戶',
                                'columns' => [
                                    ['name' => 'id', 'type' => 'int', 'display_name' => '客戶編號'],
                                    ['name' => 'name', 'type' => 'varchar', 'display_name' => '客戶名稱'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        return new QueryEngine(
            llm: $llm,
            introspector: new SchemaIntrospector($config),
            validator: new SqlValidator,
            executor: $executor,
            confidenceEstimator: new ConfidenceEstimator,
            logger: new NullLogger,
        );
    }
}
