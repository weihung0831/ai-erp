<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\TenantRepository;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepositoryBindingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_repository_interface_resolves_to_eloquent_implementation(): void
    {
        $repo = $this->app->make(UserRepositoryInterface::class);

        $this->assertInstanceOf(UserRepository::class, $repo);
    }

    public function test_tenant_repository_interface_resolves_to_eloquent_implementation(): void
    {
        $repo = $this->app->make(TenantRepositoryInterface::class);

        $this->assertInstanceOf(TenantRepository::class, $repo);
    }

    public function test_user_repository_find_by_email_returns_matching_user(): void
    {
        $user = User::factory()->create(['email' => 'wanted@example.com']);
        User::factory()->create(['email' => 'other@example.com']);

        /** @var UserRepositoryInterface $repo */
        $repo = $this->app->make(UserRepositoryInterface::class);

        $found = $repo->findByEmail('wanted@example.com');

        $this->assertNotNull($found);
        $this->assertSame($user->id, $found->id);
    }

    public function test_user_repository_find_by_email_returns_null_when_missing(): void
    {
        /** @var UserRepositoryInterface $repo */
        $repo = $this->app->make(UserRepositoryInterface::class);

        $this->assertNull($repo->findByEmail('nobody@example.com'));
    }

    public function test_tenant_repository_find_returns_matching_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        /** @var TenantRepositoryInterface $repo */
        $repo = $this->app->make(TenantRepositoryInterface::class);

        $this->assertSame($tenant->id, $repo->find($tenant->id)?->id);
    }

    public function test_tenant_repository_find_returns_null_when_missing(): void
    {
        /** @var TenantRepositoryInterface $repo */
        $repo = $this->app->make(TenantRepositoryInterface::class);

        $this->assertNull($repo->find(999_999));
    }
}
