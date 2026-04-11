<?php

namespace Tests\Feature\Api;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Ai\LlmGateway;
use App\Services\Ai\LlmResponse;
use App\Services\Tenant\TenantQueryExecutor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Fakes\FakeLlmGateway;
use Tests\Fakes\FakeTenantQueryExecutor;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    private FakeLlmGateway $llm;

    private FakeTenantQueryExecutor $executor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->forTenant($this->tenant)->create();

        $this->llm = new FakeLlmGateway;
        $this->executor = new FakeTenantQueryExecutor;

        // 覆蓋 production binding，讓 QueryEngine 拿到 Fake 實作
        $this->app->instance(LlmGateway::class, $this->llm);
        $this->app->instance(TenantQueryExecutor::class, $this->executor);

        // 為 dynamic tenant id 塞 schema fixture。config()->set 走 dot 路徑
        // 只會覆寫該 key，不影響 config/schema_fixtures.php 其他 tenant 的資料。
        config()->set("schema_fixtures.tenants.{$this->tenant->id}", [
            'domain_context' => '餐飲業',
            'tables' => [
                [
                    'name' => 'orders',
                    'display_name' => '訂單',
                    'columns' => [
                        ['name' => 'id', 'type' => 'int', 'display_name' => '訂單編號'],
                        ['name' => 'total_amount', 'type' => 'decimal', 'display_name' => '訂單金額'],
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
        ]);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->postJson('/api/chat', ['message' => '這個月營收'])
            ->assertUnauthorized();
    }

    public function test_missing_message_returns_422(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/chat', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['message' => '請輸入您的問題']);
    }

    public function test_too_long_message_returns_422(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/chat', ['message' => str_repeat('a', 1001)])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['message' => '問題太長，請限制在 1000 字以內']);
    }

    public function test_currency_happy_path_returns_formatted_numeric_result(): void
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

        $response = $this->actingAs($this->user)
            ->postJson('/api/chat', ['message' => '這個月營收多少']);

        $response->assertOk()
            ->assertJsonStructure([
                'reply',
                'confidence',
                'type',
                'data' => ['value', 'value_format'],
                'sql',
                'tokens_used',
            ])
            ->assertJson([
                'reply' => '本月營收為 NT$1,234,567',
                'type' => 'numeric',
                'confidence' => 0.97,
                'data' => [
                    'value' => 1234567,
                    'value_format' => 'currency',
                ],
                'sql' => 'SELECT SUM(total_amount) AS total FROM orders',
                'tokens_used' => 1847,
            ]);
    }

    public function test_count_happy_path_returns_thousands_formatted_reply(): void
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

        $this->actingAs($this->user)
            ->postJson('/api/chat', ['message' => '有幾個客戶'])
            ->assertOk()
            ->assertJson([
                'reply' => '目前有 12,345 位客戶',
                'type' => 'numeric',
                'data' => ['value' => 12345, 'value_format' => 'count'],
            ]);
    }

    public function test_llm_failure_returns_200_with_error_type(): void
    {
        $this->llm->shouldFailWith(new RuntimeException('timeout'));

        $this->actingAs($this->user)
            ->postJson('/api/chat', ['message' => '營收'])
            ->assertOk()
            ->assertJson([
                'type' => 'error',
                'reply' => '系統忙碌，請稍後再試',
            ]);
    }

    public function test_passes_current_user_tenant_id_to_query_engine(): void
    {
        $this->llm->queueResponse(new LlmResponse(
            functionName: 'execute_query',
            functionArguments: [
                'sql' => 'SELECT COUNT(*) AS c FROM orders',
                'reply_template' => '{value}',
                'value_format' => 'count',
                'confidence' => 0.99,
            ],
            content: null,
            tokensUsed: 100,
        ));
        $this->executor->queueResult([['c' => 1]]);

        $this->actingAs($this->user)
            ->postJson('/api/chat', ['message' => 'x'])
            ->assertOk();

        $this->assertSame($this->tenant->id, $this->executor->calls[0]['tenantId']);
    }
}
