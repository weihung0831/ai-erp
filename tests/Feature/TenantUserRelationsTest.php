<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantUserRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_factory_creates_valid_row(): void
    {
        $tenant = Tenant::factory()->create();

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);
        $this->assertNotEmpty($tenant->name);
        $this->assertNotEmpty($tenant->db_name);
        $this->assertContains($tenant->industry, ['restaurant', 'manufacturing', 'trading', 'retail']);
    }

    public function test_user_factory_auto_creates_tenant(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->tenant_id);
        $this->assertInstanceOf(Tenant::class, $user->tenant);
        $this->assertTrue($user->tenant->exists);
    }

    public function test_user_role_defaults_to_user_enum(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(UserRole::class, $user->role);
        $this->assertSame(UserRole::User, $user->role);
        $this->assertFalse($user->isAdmin());
    }

    public function test_admin_state_sets_role_to_admin(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertTrue($user->isAdmin());
    }

    public function test_for_tenant_state_attaches_to_existing_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create();

        $this->assertSame($tenant->id, $user->tenant_id);
    }

    public function test_tenant_has_many_users_relation(): void
    {
        $tenant = Tenant::factory()->create();
        User::factory()->forTenant($tenant)->count(3)->create();

        $this->assertCount(3, $tenant->users);
        $this->assertInstanceOf(User::class, $tenant->users->first());
    }

    public function test_deleting_tenant_with_users_is_restricted(): void
    {
        $tenant = Tenant::factory()->create();
        User::factory()->forTenant($tenant)->create();

        $this->expectException(QueryException::class);

        $tenant->delete();
    }
}
