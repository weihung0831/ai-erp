<?php

namespace Tests\Feature\Api;

use App\Models\ChatHistory;
use App\Models\Conversation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Ai\LlmGateway;
use App\Services\Ai\LlmResponse;
use App\Services\Tenant\TenantQueryExecutor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Fakes\FakeLlmGateway;
use Tests\Fakes\FakeTenantQueryExecutor;
use Tests\TestCase;

class MultiTurnConversationTest extends TestCase
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

        $this->app->instance(LlmGateway::class, $this->llm);
        $this->app->instance(TenantQueryExecutor::class, $this->executor);

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
            ],
        ]);
    }

    // ── 新對話 ──────────────────────────────────────────────

    public function test_new_conversation_returns_uuid(): void
    {
        $this->queueScalarResponse(1000, 0.97);

        $response = $this->actingAs($this->user)
            ->postJson('/api/chat', ['message' => '這個月營收多少']);

        $response->assertOk()
            ->assertJsonStructure(['conversation_id']);

        $this->assertTrue(Str::isUuid($response->json('conversation_id')));
    }

    public function test_new_conversation_creates_conversation_and_turn(): void
    {
        $this->queueScalarResponse(1000, 0.97);

        $response = $this->actingAs($this->user)
            ->postJson('/api/chat', ['message' => '這個月營收多少']);

        $uuid = $response->json('conversation_id');

        $this->assertDatabaseHas('conversations', [
            'uuid' => $uuid,
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'message_count' => 1,
        ]);

        $conversation = Conversation::where('uuid', $uuid)->first();
        $this->assertDatabaseHas('chat_histories', [
            'conversation_id' => $conversation->id,
            'message' => '這個月營收多少',
        ]);
    }

    public function test_conversation_title_is_first_message_truncated(): void
    {
        $longMessage = str_repeat('很長的訊息', 20); // 100 字
        $this->queueScalarResponse(1000, 0.97);

        $response = $this->actingAs($this->user)
            ->postJson('/api/chat', ['message' => $longMessage]);

        $uuid = $response->json('conversation_id');
        $conversation = Conversation::where('uuid', $uuid)->first();

        $this->assertSame(mb_substr($longMessage, 0, 50), $conversation->title);
    }

    // ── 多輪對話上下文 ─────────────────────────────────────

    public function test_continuing_conversation_sends_history_to_llm(): void
    {
        // 第一輪
        $this->queueScalarResponse(1000, 0.97);
        $first = $this->actingAs($this->user)
            ->postJson('/api/chat', ['message' => '這個月營收多少']);
        $conversationUuid = $first->json('conversation_id');

        // 第二輪
        $this->queueScalarResponse(800, 0.95);
        $this->actingAs($this->user)
            ->postJson('/api/chat', [
                'message' => '跟上個月比呢',
                'conversation_id' => $conversationUuid,
            ])
            ->assertOk();

        // 驗證 LLM 收到的 messages 包含歷史
        $lastCall = $this->llm->lastCall();
        $messages = $lastCall['messages'];

        // [system, user(R1), assistant(R1), user(R2)]
        $this->assertCount(4, $messages);
        $this->assertSame('system', $messages[0]['role']);
        $this->assertSame('這個月營收多少', $messages[1]['content']);
        $this->assertSame('assistant', $messages[2]['role']);
        $this->assertSame('跟上個月比呢', $messages[3]['content']);
    }

    public function test_both_turns_persisted_with_correct_count(): void
    {
        $this->queueScalarResponse(1000, 0.97);
        $first = $this->actingAs($this->user)
            ->postJson('/api/chat', ['message' => '營收']);
        $uuid = $first->json('conversation_id');

        $this->queueScalarResponse(800, 0.95);
        $this->actingAs($this->user)
            ->postJson('/api/chat', [
                'message' => '跟上個月比',
                'conversation_id' => $uuid,
            ]);

        $conversation = Conversation::where('uuid', $uuid)->first();
        $this->assertSame(2, $conversation->message_count);
        $this->assertSame(2, $conversation->messages()->count());
    }

    // ── 租戶隔離 ──────────────────────────────────────────

    public function test_cannot_access_other_users_conversation(): void
    {
        $this->queueScalarResponse(1000, 0.97);
        $response = $this->actingAs($this->user)
            ->postJson('/api/chat', ['message' => '營收']);
        $uuid = $response->json('conversation_id');

        $otherUser = User::factory()->forTenant($this->tenant)->create();

        $this->actingAs($otherUser)
            ->postJson('/api/chat', [
                'message' => '接續',
                'conversation_id' => $uuid,
            ])
            ->assertStatus(403)
            ->assertJson(['message' => '對話不存在或無權存取']);
    }

    public function test_cannot_access_other_tenants_conversation(): void
    {
        $this->queueScalarResponse(1000, 0.97);
        $response = $this->actingAs($this->user)
            ->postJson('/api/chat', ['message' => '營收']);
        $uuid = $response->json('conversation_id');

        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->forTenant($otherTenant)->create();

        config()->set("schema_fixtures.tenants.{$otherTenant->id}", [
            'domain_context' => '製造業',
            'tables' => [],
        ]);

        $this->actingAs($otherUser)
            ->postJson('/api/chat', [
                'message' => '接續',
                'conversation_id' => $uuid,
            ])
            ->assertStatus(403);
    }

    // ── 50 輪上限 ─────────────────────────────────────────

    public function test_rejects_conversation_exceeding_50_turns(): void
    {
        $conversation = Conversation::factory()
            ->forUser($this->user)
            ->create(['message_count' => 50]);

        ChatHistory::factory()
            ->count(50)
            ->forConversation($conversation)
            ->create();

        $this->actingAs($this->user)
            ->postJson('/api/chat', [
                'message' => '第 51 輪',
                'conversation_id' => $conversation->uuid,
            ])
            ->assertStatus(422)
            ->assertJson(['message' => '此對話已達 50 輪上限，請開啟新對話']);
    }

    // ── conversation_id 格式驗證 ──────────────────────────

    public function test_invalid_conversation_id_format_returns_422(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/chat', [
                'message' => '測試',
                'conversation_id' => 'not-a-uuid',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['conversation_id']);
    }

    public function test_nonexistent_conversation_id_returns_403(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/chat', [
                'message' => '測試',
                'conversation_id' => Str::uuid()->toString(),
            ])
            ->assertStatus(403);
    }

    // ── History API ───────────────────────────────────────

    public function test_history_index_returns_user_conversations(): void
    {
        $conversation = Conversation::factory()
            ->forUser($this->user)
            ->create(['message_count' => 3]);

        ChatHistory::factory()
            ->count(3)
            ->forConversation($conversation)
            ->create();

        $this->actingAs($this->user)
            ->getJson('/api/chat/history')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.conversation_id', $conversation->uuid)
            ->assertJsonPath('data.0.message_count', 3);
    }

    public function test_history_index_excludes_other_users(): void
    {
        $otherUser = User::factory()->forTenant($this->tenant)->create();

        Conversation::factory()->forUser($otherUser)->create();

        $this->actingAs($this->user)
            ->getJson('/api/chat/history')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_history_show_returns_conversation_messages(): void
    {
        $conversation = Conversation::factory()
            ->forUser($this->user)
            ->create();

        ChatHistory::factory()
            ->count(3)
            ->forConversation($conversation)
            ->create();

        $this->actingAs($this->user)
            ->getJson("/api/chat/history/{$conversation->uuid}")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [
                ['message', 'response', 'response_type', 'response_data', 'confidence', 'created_at'],
            ]]);
    }

    public function test_history_show_rejects_other_users_conversation(): void
    {
        $otherUser = User::factory()->forTenant($this->tenant)->create();
        $conversation = Conversation::factory()->forUser($otherUser)->create();

        $this->actingAs($this->user)
            ->getJson("/api/chat/history/{$conversation->uuid}")
            ->assertStatus(403);
    }

    // ── LLM context window 上限 10 輪 ────────────────────

    public function test_llm_receives_at_most_10_turns_of_history(): void
    {
        $conversation = Conversation::factory()
            ->forUser($this->user)
            ->create(['message_count' => 12]);

        ChatHistory::factory()
            ->count(12)
            ->forConversation($conversation)
            ->create();

        $this->queueScalarResponse(500, 0.95);

        $this->actingAs($this->user)
            ->postJson('/api/chat', [
                'message' => '第 13 輪',
                'conversation_id' => $conversation->uuid,
            ])
            ->assertOk();

        $lastCall = $this->llm->lastCall();
        $messages = $lastCall['messages'];

        // system(1) + history(10 turns × 2) + current user(1) = 22
        $this->assertCount(22, $messages);
    }

    // ── Helper ────────────────────────────────────────────

    private function queueScalarResponse(int|float $value, float $confidence): void
    {
        $this->llm->queueResponse(new LlmResponse(
            functionName: 'execute_query',
            functionArguments: [
                'sql' => 'SELECT SUM(total_amount) AS total FROM orders',
                'reply_template' => '結果為 {value}',
                'value_format' => 'currency',
                'confidence' => $confidence,
            ],
            content: null,
            tokensUsed: 500,
        ));
        $this->executor->queueResult([['total' => $value]]);
    }
}
