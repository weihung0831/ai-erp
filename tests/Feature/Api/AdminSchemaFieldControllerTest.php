<?php

namespace Tests\Feature\Api;

use App\Models\SchemaFieldRestriction;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Schema\ConfigSchemaIntrospector;
use App\Services\Schema\SchemaIntrospector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSchemaFieldControllerTest extends TestCase
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

        $this->app->bind(SchemaIntrospector::class, ConfigSchemaIntrospector::class);
    }

    // ── index ──

    public function test_admin_can_list_schema_fields(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/schema-fields');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['table', 'table_display_name', 'column', 'column_display_name', 'type', 'is_restricted']],
            ]);

        // 預設 fixture（tenant 1）全部欄位都不 restricted
        foreach ($response->json('data') as $field) {
            $this->assertFalse($field['is_restricted']);
        }
    }

    public function test_index_reflects_db_restriction_override(): void
    {
        SchemaFieldRestriction::factory()->forTenant($this->tenant)->create([
            'table_name' => 'orders',
            'column_name' => 'total_amount',
            'is_restricted' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/schema-fields');

        $response->assertOk();

        $totalAmount = collect($response->json('data'))
            ->firstWhere('column', 'total_amount');

        $this->assertNotNull($totalAmount);
        $this->assertTrue($totalAmount['is_restricted']);
    }

    public function test_non_admin_gets_403_on_index(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/admin/schema-fields')
            ->assertForbidden();
    }

    public function test_unauthenticated_gets_401_on_index(): void
    {
        $this->getJson('/api/admin/schema-fields')
            ->assertUnauthorized();
    }

    // ── update ──

    public function test_admin_can_restrict_a_field(): void
    {
        $response = $this->actingAs($this->admin)
            ->patchJson('/api/admin/schema-fields/orders/total_amount', [
                'is_restricted' => true,
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'table' => 'orders',
                    'column' => 'total_amount',
                    'is_restricted' => true,
                ],
            ]);

        $this->assertDatabaseHas('schema_field_restrictions', [
            'tenant_id' => $this->tenant->id,
            'table_name' => 'orders',
            'column_name' => 'total_amount',
            'is_restricted' => true,
        ]);
    }

    public function test_admin_can_unrestrict_a_field(): void
    {
        SchemaFieldRestriction::factory()->forTenant($this->tenant)->create([
            'table_name' => 'orders',
            'column_name' => 'total_amount',
            'is_restricted' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->patchJson('/api/admin/schema-fields/orders/total_amount', [
                'is_restricted' => false,
            ]);

        $response->assertOk()
            ->assertJson(['data' => ['is_restricted' => false]]);

        $this->assertDatabaseHas('schema_field_restrictions', [
            'tenant_id' => $this->tenant->id,
            'table_name' => 'orders',
            'column_name' => 'total_amount',
            'is_restricted' => false,
        ]);
    }

    public function test_update_nonexistent_column_returns_404(): void
    {
        $this->actingAs($this->admin)
            ->patchJson('/api/admin/schema-fields/orders/nonexistent_column', [
                'is_restricted' => true,
            ])
            ->assertNotFound();
    }

    public function test_update_nonexistent_table_returns_404(): void
    {
        $this->actingAs($this->admin)
            ->patchJson('/api/admin/schema-fields/nonexistent_table/id', [
                'is_restricted' => true,
            ])
            ->assertNotFound();
    }

    public function test_update_validates_is_restricted_required(): void
    {
        $this->actingAs($this->admin)
            ->patchJson('/api/admin/schema-fields/orders/total_amount', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['is_restricted' => '請指定欄位是否受限']);
    }

    public function test_update_validates_is_restricted_boolean(): void
    {
        $this->actingAs($this->admin)
            ->patchJson('/api/admin/schema-fields/orders/total_amount', [
                'is_restricted' => 'yes',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['is_restricted']);
    }

    public function test_non_admin_cannot_update(): void
    {
        $this->actingAs($this->user)
            ->patchJson('/api/admin/schema-fields/orders/total_amount', [
                'is_restricted' => true,
            ])
            ->assertForbidden();
    }
}
