<?php

namespace Tests\Feature\Api;

use App\Models\QuickAction;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickActionControllerTest extends TestCase
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

    public function test_user_can_list_active_quick_actions(): void
    {
        QuickAction::factory()->forTenant($this->tenant)->create(['sort_order' => 2, 'label' => '庫存']);
        QuickAction::factory()->forTenant($this->tenant)->create(['sort_order' => 1, 'label' => '營收']);
        QuickAction::factory()->forTenant($this->tenant)->inactive()->create(['label' => '隱藏']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/quick-actions');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'label', 'prompt']]]);

        // 確認排序（sort_order 1 在前）
        $this->assertSame('營收', $response->json('data.0.label'));
        $this->assertSame('庫存', $response->json('data.1.label'));

        // 確認不回傳 admin 欄位
        $this->assertArrayNotHasKey('sort_order', $response->json('data.0'));
        $this->assertArrayNotHasKey('is_active', $response->json('data.0'));
    }

    public function test_user_only_sees_own_tenant_actions(): void
    {
        $otherTenant = Tenant::factory()->create();
        QuickAction::factory()->forTenant($this->tenant)->create();
        QuickAction::factory()->forTenant($otherTenant)->create();

        $this->actingAs($this->user)
            ->getJson('/api/quick-actions')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_empty_result_returns_empty_array(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/quick-actions')
            ->assertOk()
            ->assertJson(['data' => []]);
    }

    public function test_unauthenticated_gets_401(): void
    {
        $this->getJson('/api/quick-actions')
            ->assertUnauthorized();
    }
}
