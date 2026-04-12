<?php

namespace Tests\Unit;

use App\Models\QuickAction;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickActionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_valid_row(): void
    {
        $qa = QuickAction::factory()->create();

        $this->assertDatabaseHas('quick_actions', ['id' => $qa->id]);
        $this->assertNotEmpty($qa->label);
        $this->assertNotEmpty($qa->prompt);
        $this->assertTrue($qa->is_active);
    }

    public function test_belongs_to_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $qa = QuickAction::factory()->forTenant($tenant)->create();

        $this->assertSame($tenant->id, $qa->tenant_id);
        $this->assertInstanceOf(Tenant::class, $qa->tenant);
    }

    public function test_inactive_state(): void
    {
        $qa = QuickAction::factory()->inactive()->create();

        $this->assertFalse($qa->is_active);
    }

    public function test_casts_are_correct(): void
    {
        $qa = QuickAction::factory()->create(['sort_order' => 3]);

        $this->assertIsInt($qa->sort_order);
        $this->assertIsBool($qa->is_active);
    }

    public function test_cascade_delete_with_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $qa = QuickAction::factory()->forTenant($tenant)->create();

        $tenant->delete();

        $this->assertDatabaseMissing('quick_actions', ['id' => $qa->id]);
    }
}
