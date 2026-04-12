<?php

namespace Tests\Feature\Api;

use App\DataTransferObjects\Dashboard\DashboardMetric;
use App\Enums\AggregationType;
use App\Enums\ValueFormat;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Dashboard\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
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

    public function test_dashboard_requires_authentication(): void
    {
        $this->getJson('/api/dashboard')->assertUnauthorized();
    }

    public function test_dashboard_returns_metrics_from_service(): void
    {
        $this->mock(DashboardService::class)
            ->shouldReceive('getMetrics')
            ->with($this->tenant->id)
            ->once()
            ->andReturn([
                new DashboardMetric(
                    label: '訂單金額',
                    tableName: 'orders',
                    columnName: 'total_amount',
                    aggregation: AggregationType::Sum,
                    valueFormat: ValueFormat::Currency,
                    value: 1234567,
                    formattedValue: 'NT$1,234,567',
                ),
                new DashboardMetric(
                    label: '訂單編號',
                    tableName: 'orders',
                    columnName: 'id',
                    aggregation: AggregationType::Count,
                    valueFormat: ValueFormat::Count,
                    value: 342,
                    formattedValue: '342',
                ),
            ]);

        $this->actingAs($this->user)
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [[
                    'label',
                    'table_name',
                    'column_name',
                    'aggregation',
                    'value_format',
                    'value',
                    'formatted_value',
                ]],
            ])
            ->assertJsonPath('data.0.label', '訂單金額')
            ->assertJsonPath('data.0.value', 1234567)
            ->assertJsonPath('data.0.formatted_value', 'NT$1,234,567')
            ->assertJsonPath('data.1.label', '訂單編號')
            ->assertJsonPath('data.1.value', 342)
            ->assertJsonPath('data.1.aggregation', 'count');
    }

    public function test_dashboard_returns_empty_when_no_kpi_configured(): void
    {
        $this->mock(DashboardService::class)
            ->shouldReceive('getMetrics')
            ->with($this->tenant->id)
            ->once()
            ->andReturn([]);

        $this->actingAs($this->user)
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
