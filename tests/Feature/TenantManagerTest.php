<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Services\Tenant\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class TenantManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_state_has_no_tenant(): void
    {
        $manager = $this->app->make(TenantManager::class);

        $this->assertFalse($manager->hasTenant());
        $this->assertNull($manager->current());
    }

    public function test_switch_to_stores_current_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $manager = $this->app->make(TenantManager::class);

        $manager->switchTo($tenant);

        $this->assertTrue($manager->hasTenant());
        $this->assertSame($tenant->id, $manager->current()?->id);
    }

    public function test_forget_clears_current_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $manager = $this->app->make(TenantManager::class);
        $manager->switchTo($tenant);

        $manager->forget();

        $this->assertFalse($manager->hasTenant());
        $this->assertNull($manager->current());
    }

    public function test_current_or_fail_throws_when_no_tenant_set(): void
    {
        $manager = $this->app->make(TenantManager::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('尚未設定租戶上下文');

        $manager->currentOrFail();
    }

    public function test_current_or_fail_returns_tenant_when_set(): void
    {
        $tenant = Tenant::factory()->create();
        $manager = $this->app->make(TenantManager::class);
        $manager->switchTo($tenant);

        $this->assertSame($tenant->id, $manager->currentOrFail()->id);
    }

    public function test_manager_is_scoped_per_request(): void
    {
        $first = $this->app->make(TenantManager::class);
        $second = $this->app->make(TenantManager::class);

        $this->assertSame($first, $second, 'scoped binding should reuse the same instance within a request');
    }
}
