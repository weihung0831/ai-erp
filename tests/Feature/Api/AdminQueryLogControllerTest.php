<?php

namespace Tests\Feature\Api;

use App\Models\QueryLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminQueryLogControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->admin = User::factory()->forTenant($this->tenant)->admin()->create();
        $this->user = User::factory()->forTenant($this->tenant)->create();
    }

    // ── index ──

    public function test_admin_can_list_query_logs(): void
    {
        QueryLog::factory()->forTenantUser($this->tenant, $this->user)->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/query-logs');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'tenant_id', 'user_id', 'question', 'reply', 'sql_executed', 'confidence', 'tokens_used', 'is_correct', 'created_at']],
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_index_returns_empty_when_no_logs(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/query-logs');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_index_scoped_to_tenant(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->forTenant($otherTenant)->create();

        QueryLog::factory()->forTenantUser($this->tenant, $this->user)->count(2)->create();
        QueryLog::factory()->forTenantUser($otherTenant, $otherUser)->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/query-logs');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_index_filter_by_user_id(): void
    {
        $otherUser = User::factory()->forTenant($this->tenant)->create();

        QueryLog::factory()->forTenantUser($this->tenant, $this->user)->count(2)->create();
        QueryLog::factory()->forTenantUser($this->tenant, $otherUser)->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/query-logs?user_id='.$this->user->id);

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_index_filter_by_date_range(): void
    {
        QueryLog::factory()->forTenantUser($this->tenant, $this->user)->create([
            'created_at' => '2026-04-01 10:00:00',
        ]);
        QueryLog::factory()->forTenantUser($this->tenant, $this->user)->create([
            'created_at' => '2026-04-10 10:00:00',
        ]);
        QueryLog::factory()->forTenantUser($this->tenant, $this->user)->create([
            'created_at' => '2026-04-20 10:00:00',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/query-logs?date_from=2026-04-05&date_to=2026-04-15');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_index_filter_by_is_correct(): void
    {
        QueryLog::factory()->forTenantUser($this->tenant, $this->user)->correct()->count(2)->create();
        QueryLog::factory()->forTenantUser($this->tenant, $this->user)->incorrect()->create();
        QueryLog::factory()->forTenantUser($this->tenant, $this->user)->create(); // unreviewed

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/query-logs?is_correct=1');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_index_supports_pagination(): void
    {
        QueryLog::factory()->forTenantUser($this->tenant, $this->user)->count(25)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/query-logs?per_page=10');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('last_page', 3);
    }

    public function test_non_admin_gets_403_on_index(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/admin/query-logs')
            ->assertForbidden();
    }

    public function test_unauthenticated_gets_401_on_index(): void
    {
        $this->getJson('/api/admin/query-logs')
            ->assertUnauthorized();
    }

    // ── update (mark accuracy) ──

    public function test_admin_can_mark_query_as_correct(): void
    {
        $log = QueryLog::factory()->forTenantUser($this->tenant, $this->user)->create();

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/admin/query-logs/{$log->id}", [
                'is_correct' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $log->id)
            ->assertJsonPath('data.is_correct', true);

        $this->assertNotNull($response->json('data.reviewed_at'));

        $this->assertDatabaseHas('query_logs', [
            'id' => $log->id,
            'is_correct' => true,
        ]);
    }

    public function test_admin_can_mark_query_as_incorrect(): void
    {
        $log = QueryLog::factory()->forTenantUser($this->tenant, $this->user)->correct()->create();

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/admin/query-logs/{$log->id}", [
                'is_correct' => false,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.is_correct', false);

        $this->assertDatabaseHas('query_logs', [
            'id' => $log->id,
            'is_correct' => false,
        ]);
    }

    public function test_update_nonexistent_log_returns_404(): void
    {
        $this->actingAs($this->admin)
            ->patchJson('/api/admin/query-logs/99999', [
                'is_correct' => true,
            ])
            ->assertNotFound();
    }

    public function test_update_other_tenant_log_returns_404(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->forTenant($otherTenant)->create();
        $log = QueryLog::factory()->forTenantUser($otherTenant, $otherUser)->create();

        $this->actingAs($this->admin)
            ->patchJson("/api/admin/query-logs/{$log->id}", [
                'is_correct' => true,
            ])
            ->assertNotFound();
    }

    public function test_update_validates_is_correct_required(): void
    {
        $log = QueryLog::factory()->forTenantUser($this->tenant, $this->user)->create();

        $this->actingAs($this->admin)
            ->patchJson("/api/admin/query-logs/{$log->id}", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['is_correct' => '請指定查詢結果是否正確']);
    }

    public function test_update_validates_is_correct_boolean(): void
    {
        $log = QueryLog::factory()->forTenantUser($this->tenant, $this->user)->create();

        $this->actingAs($this->admin)
            ->patchJson("/api/admin/query-logs/{$log->id}", [
                'is_correct' => 'yes',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['is_correct']);
    }

    public function test_non_admin_cannot_update(): void
    {
        $log = QueryLog::factory()->forTenantUser($this->tenant, $this->user)->create();

        $this->actingAs($this->user)
            ->patchJson("/api/admin/query-logs/{$log->id}", [
                'is_correct' => true,
            ])
            ->assertForbidden();
    }
}
