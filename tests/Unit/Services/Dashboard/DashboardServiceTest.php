<?php

namespace Tests\Unit\Services\Dashboard;

use App\Enums\AggregationType;
use App\Enums\ValueFormat;
use App\Services\Dashboard\DashboardService;
use App\Services\Tenant\TenantManager;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    private MockInterface&DatabaseManager $db;

    private MockInterface&Connection $connection;

    private DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = Mockery::mock(Connection::class);
        $this->db = Mockery::mock(DatabaseManager::class);
        $this->db->shouldReceive('connection')
            ->with(TenantManager::connectionName(1))
            ->andReturn($this->connection);

        $this->service = new DashboardService($this->db);
    }

    #[Test]
    public function it_returns_metrics_for_kpi_fields(): void
    {
        $this->stubKpiFields([
            (object) [
                'table_name' => 'orders',
                'column_name' => 'total_amount',
                'display_name' => '訂單金額',
                'aggregation' => 'sum',
                'value_format' => 'currency',
            ],
            (object) [
                'table_name' => 'orders',
                'column_name' => 'id',
                'display_name' => '訂單編號',
                'aggregation' => 'count',
                'value_format' => 'count',
            ],
        ]);

        // Same-table KPIs are combined into a single query
        $this->connection->shouldReceive('selectOne')
            ->with('SELECT SUM(`total_amount`) AS metric_0, COUNT(`id`) AS metric_1 FROM `orders`')
            ->once()
            ->andReturn((object) ['metric_0' => 1234567, 'metric_1' => 342]);

        $metrics = $this->service->getMetrics(1);

        $this->assertCount(2, $metrics);

        $this->assertSame('訂單金額', $metrics[0]->label);
        $this->assertSame(AggregationType::Sum, $metrics[0]->aggregation);
        $this->assertSame(ValueFormat::Currency, $metrics[0]->valueFormat);
        $this->assertSame(1234567, $metrics[0]->value);
        $this->assertSame('NT$1,234,567', $metrics[0]->formattedValue);

        $this->assertSame('訂單編號', $metrics[1]->label);
        $this->assertSame(AggregationType::Count, $metrics[1]->aggregation);
        $this->assertSame(342, $metrics[1]->value);
        $this->assertSame('342', $metrics[1]->formattedValue);
    }

    #[Test]
    public function it_returns_empty_when_no_kpi_fields(): void
    {
        $this->stubKpiFields([]);

        $metrics = $this->service->getMetrics(1);

        $this->assertCount(0, $metrics);
    }

    #[Test]
    public function it_skips_fields_with_invalid_aggregation(): void
    {
        $this->stubKpiFields([
            (object) [
                'table_name' => 'orders',
                'column_name' => 'total_amount',
                'display_name' => '訂單金額',
                'aggregation' => 'invalid',
                'value_format' => 'currency',
            ],
        ]);

        $metrics = $this->service->getMetrics(1);

        $this->assertCount(0, $metrics);
    }

    #[Test]
    public function it_handles_null_metric_value_as_zero(): void
    {
        $this->stubKpiFields([
            (object) [
                'table_name' => 'orders',
                'column_name' => 'total_amount',
                'display_name' => '訂單金額',
                'aggregation' => 'sum',
                'value_format' => 'currency',
            ],
        ]);

        $this->connection->shouldReceive('selectOne')
            ->with('SELECT SUM(`total_amount`) AS metric_0 FROM `orders`')
            ->andReturn((object) ['metric_0' => null]);

        $metrics = $this->service->getMetrics(1);

        $this->assertCount(1, $metrics);
        $this->assertSame(0, $metrics[0]->value);
        $this->assertSame('NT$0', $metrics[0]->formattedValue);
    }

    #[Test]
    public function it_supports_all_aggregation_types(): void
    {
        $this->stubKpiFields([
            (object) ['table_name' => 'payments', 'column_name' => 'amount', 'display_name' => 'SUM', 'aggregation' => 'sum', 'value_format' => 'currency'],
            (object) ['table_name' => 'orders', 'column_name' => 'id', 'display_name' => 'COUNT', 'aggregation' => 'count', 'value_format' => 'count'],
            (object) ['table_name' => 'products', 'column_name' => 'cost', 'display_name' => 'AVG', 'aggregation' => 'avg', 'value_format' => 'currency'],
            (object) ['table_name' => 'invoices', 'column_name' => 'amount', 'display_name' => 'MAX', 'aggregation' => 'max', 'value_format' => 'currency'],
            (object) ['table_name' => 'expenses', 'column_name' => 'amount', 'display_name' => 'MIN', 'aggregation' => 'min', 'value_format' => 'currency'],
        ]);

        // Each table has 1 KPI, so 5 separate queries (1 per table)
        $this->connection->shouldReceive('selectOne')
            ->with('SELECT SUM(`amount`) AS metric_0 FROM `payments`')
            ->once()->andReturn((object) ['metric_0' => 100]);
        $this->connection->shouldReceive('selectOne')
            ->with('SELECT COUNT(`id`) AS metric_0 FROM `orders`')
            ->once()->andReturn((object) ['metric_0' => 200]);
        $this->connection->shouldReceive('selectOne')
            ->with('SELECT AVG(`cost`) AS metric_0 FROM `products`')
            ->once()->andReturn((object) ['metric_0' => 300]);
        $this->connection->shouldReceive('selectOne')
            ->with('SELECT MAX(`amount`) AS metric_0 FROM `invoices`')
            ->once()->andReturn((object) ['metric_0' => 400]);
        $this->connection->shouldReceive('selectOne')
            ->with('SELECT MIN(`amount`) AS metric_0 FROM `expenses`')
            ->once()->andReturn((object) ['metric_0' => 500]);

        $metrics = $this->service->getMetrics(1);

        $this->assertCount(5, $metrics);
        $this->assertSame(AggregationType::Sum, $metrics[0]->aggregation);
        $this->assertSame(AggregationType::Count, $metrics[1]->aggregation);
        $this->assertSame(AggregationType::Avg, $metrics[2]->aggregation);
        $this->assertSame(AggregationType::Max, $metrics[3]->aggregation);
        $this->assertSame(AggregationType::Min, $metrics[4]->aggregation);
    }

    private function stubKpiFields(array $rows): void
    {
        $builder = Mockery::mock(Builder::class);
        $this->connection->shouldReceive('table')
            ->with('schema_metadata')
            ->andReturn($builder);

        $builder->shouldReceive('where')->with('is_kpi', true)->andReturnSelf();
        $builder->shouldReceive('whereNotNull')->with('column_name')->andReturnSelf();
        $builder->shouldReceive('whereNotNull')->with('aggregation')->andReturnSelf();
        $builder->shouldReceive('whereNotNull')->with('value_format')->andReturnSelf();
        $builder->shouldReceive('orderBy')->with('id')->andReturnSelf();
        $builder->shouldReceive('get')->andReturn(collect($rows));
    }
}
