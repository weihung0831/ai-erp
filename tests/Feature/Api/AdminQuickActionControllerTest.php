<?php

namespace Tests\Feature\Api;

use App\Models\QuickAction;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminQuickActionControllerTest extends TestCase
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

    public function test_admin_can_list_all_quick_actions_including_inactive(): void
    {
        QuickAction::factory()->forTenant($this->tenant)->create(['sort_order' => 1, 'is_active' => true]);
        QuickAction::factory()->forTenant($this->tenant)->inactive()->create(['sort_order' => 2]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/quick-actions');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'label', 'prompt', 'sort_order', 'is_active']]]);

        $this->assertTrue($response->json('data.0.is_active'));
        $this->assertFalse($response->json('data.1.is_active'));
    }

    public function test_admin_only_sees_own_tenant_actions(): void
    {
        $otherTenant = Tenant::factory()->create();
        QuickAction::factory()->forTenant($this->tenant)->create();
        QuickAction::factory()->forTenant($otherTenant)->create();

        $this->actingAs($this->admin)
            ->getJson('/api/admin/quick-actions')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_non_admin_gets_403(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/admin/quick-actions')
            ->assertForbidden();
    }

    public function test_unauthenticated_gets_401(): void
    {
        $this->getJson('/api/admin/quick-actions')
            ->assertUnauthorized();
    }

    // ── store ──

    public function test_admin_can_create_quick_action(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/quick-actions', [
                'label' => '本月營收',
                'prompt' => '這個月營收多少？',
                'sort_order' => 1,
            ]);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'label' => '本月營收',
                    'prompt' => '這個月營收多少？',
                    'sort_order' => 1,
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('quick_actions', [
            'tenant_id' => $this->tenant->id,
            'label' => '本月營收',
        ]);
    }

    public function test_sort_order_defaults_to_zero(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/quick-actions', [
                'label' => '庫存',
                'prompt' => '庫存狀況如何？',
            ]);

        $response->assertCreated();
        $this->assertSame(0, $response->json('data.sort_order'));
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/admin/quick-actions', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['label' => '請輸入按鈕文字', 'prompt' => '請輸入問句內容']);
    }

    public function test_store_validates_max_length(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/admin/quick-actions', [
                'label' => str_repeat('a', 31),
                'prompt' => str_repeat('b', 201),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['label' => '按鈕文字最多 30 字', 'prompt' => '問句最多 200 字']);
    }

    public function test_non_admin_cannot_store(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/admin/quick-actions', [
                'label' => '測試',
                'prompt' => '測試問句',
            ])
            ->assertForbidden();
    }

    // ── destroy ──

    public function test_admin_can_delete_own_tenant_action(): void
    {
        $qa = QuickAction::factory()->forTenant($this->tenant)->create();

        $this->actingAs($this->admin)
            ->deleteJson("/api/admin/quick-actions/{$qa->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('quick_actions', ['id' => $qa->id]);
    }

    public function test_admin_cannot_delete_other_tenant_action(): void
    {
        $otherTenant = Tenant::factory()->create();
        $qa = QuickAction::factory()->forTenant($otherTenant)->create();

        $this->actingAs($this->admin)
            ->deleteJson("/api/admin/quick-actions/{$qa->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('quick_actions', ['id' => $qa->id]);
    }

    public function test_delete_nonexistent_returns_404(): void
    {
        $this->actingAs($this->admin)
            ->deleteJson('/api/admin/quick-actions/99999')
            ->assertNotFound();
    }

    public function test_non_admin_cannot_delete(): void
    {
        $qa = QuickAction::factory()->forTenant($this->tenant)->create();

        $this->actingAs($this->user)
            ->deleteJson("/api/admin/quick-actions/{$qa->id}")
            ->assertForbidden();
    }
}
