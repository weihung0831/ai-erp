<?php

namespace Tests\Feature\Api;

use App\Models\ChatHistory;
use App\Models\Conversation;
use App\Models\QueryLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatHistoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->forTenant($this->tenant)->create();
    }

    // ── index ──────────────────────────────────────────────

    public function test_index_returns_conversations_for_current_user(): void
    {
        Conversation::factory()->forUser($this->user)->count(3)->create();

        // Other user's conversation — should not appear
        $other = User::factory()->forTenant($this->tenant)->create();
        Conversation::factory()->forUser($other)->create();

        $this->actingAs($this->user)
            ->getJson('/api/chat/history')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['conversation_id', 'title', 'message_count', 'last_active_at']]]);
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/chat/history')->assertUnauthorized();
    }

    // ── show ───────────────────────────────────────────────

    public function test_show_returns_messages_for_conversation(): void
    {
        $conv = Conversation::factory()->forUser($this->user)->create();
        ChatHistory::factory()->forConversation($conv)->count(2)->create();

        $this->actingAs($this->user)
            ->getJson("/api/chat/history/{$conv->uuid}")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['message', 'response', 'response_type', 'response_data', 'confidence', 'created_at']]]);
    }

    public function test_show_rejects_other_users_conversation(): void
    {
        $other = User::factory()->forTenant($this->tenant)->create();
        $conv = Conversation::factory()->forUser($other)->create();

        $this->actingAs($this->user)
            ->getJson("/api/chat/history/{$conv->uuid}")
            ->assertStatus(403);
    }

    // ── destroy ────────────────────────────────────────────

    public function test_destroy_deletes_conversation(): void
    {
        $conv = Conversation::factory()->forUser($this->user)->create();
        ChatHistory::factory()->forConversation($conv)->count(2)->create();

        $this->actingAs($this->user)
            ->deleteJson("/api/chat/history/{$conv->uuid}")
            ->assertOk()
            ->assertJson(['message' => '對話已刪除']);

        $this->assertDatabaseMissing('conversations', ['id' => $conv->id]);
        $this->assertDatabaseCount('chat_histories', 0);
    }

    public function test_destroy_nullifies_query_log_fk(): void
    {
        $conv = Conversation::factory()->forUser($this->user)->create();
        $turn = ChatHistory::factory()->forConversation($conv)->create();
        $log = QueryLog::factory()->create(['chat_history_id' => $turn->id]);

        $this->actingAs($this->user)
            ->deleteJson("/api/chat/history/{$conv->uuid}")
            ->assertOk();

        $this->assertDatabaseHas('query_logs', ['id' => $log->id, 'chat_history_id' => null]);
    }

    public function test_destroy_requires_authentication(): void
    {
        $conv = Conversation::factory()->forUser($this->user)->create();

        $this->deleteJson("/api/chat/history/{$conv->uuid}")
            ->assertUnauthorized();
    }

    public function test_destroy_rejects_other_users_conversation(): void
    {
        $other = User::factory()->forTenant($this->tenant)->create();
        $conv = Conversation::factory()->forUser($other)->create();

        $this->actingAs($this->user)
            ->deleteJson("/api/chat/history/{$conv->uuid}")
            ->assertStatus(403);

        $this->assertDatabaseHas('conversations', ['id' => $conv->id]);
    }

    public function test_destroy_returns_403_for_nonexistent_conversation(): void
    {
        $this->actingAs($this->user)
            ->deleteJson('/api/chat/history/nonexistent-uuid')
            ->assertStatus(403);
    }
}
