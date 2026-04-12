<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Tenant\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TenantMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 臨時註冊幾個測試用路由，模擬 API pipeline。
        Route::middleware(['auth:sanctum', 'tenant'])
            ->get('/__test/tenant-echo', fn (Request $request) => response()->json([
                'user_id' => $request->user()->id,
                'tenant_id' => app(TenantManager::class)->current()?->id,
            ]));

    }

    public function test_tenant_middleware_rejects_unauthenticated_request(): void
    {
        $this->getJson('/__test/tenant-echo')->assertUnauthorized();
    }

    public function test_tenant_middleware_sets_current_tenant_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/__test/tenant-echo');

        $response->assertOk()
            ->assertJson([
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
            ]);
    }
}
