<?php

namespace Tests\Unit\Services\Dashboard;

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
    public function it_returns_metrics_grouped_by_section(): void
    {
        $this->stubAllTables();

        $metrics = $this->service->getMetrics(1);

        $this->assertNotEmpty($metrics);

        $sections = array_unique(array_map(fn ($m) => $m->section, $metrics));
        $this->assertContains('sales', $sections);
        $this->assertContains('finance', $sections);
        $this->assertContains('operations', $sections);
    }

    #[Test]
    public function it_returns_expected_metric_labels(): void
    {
        $this->stubAllTables();

        $metrics = $this->service->getMetrics(1);

        $labels = array_map(fn ($m) => $m->label, $metrics);
        $this->assertContains('本月營收', $labels);
        $this->assertContains('本季營收', $labels);
        $this->assertContains('年度營收', $labels);
        $this->assertContains('本月平均訂單金額', $labels);
        $this->assertContains('本月應收帳款', $labels);
        $this->assertContains('本月逾期應收', $labels);
        $this->assertContains('本月新增客戶', $labels);
        $this->assertContains('本月新增產品', $labels);
        $this->assertContains('本月採購單', $labels);
    }

    #[Test]
    public function it_formats_currency_values(): void
    {
        $this->stubAllTables(1234567);

        $metrics = $this->service->getMetrics(1);
        $revenue = collect($metrics)->firstWhere('label', '本月營收');

        $this->assertSame('NT$1,234,567', $revenue->formattedValue);
    }

    #[Test]
    public function it_handles_zero_values(): void
    {
        $this->stubAllTables(0);

        $metrics = $this->service->getMetrics(1);

        foreach ($metrics as $m) {
            $this->assertTrue($m->trend === null || $m->trend === 0.0);
        }
    }

    private function stubAllTables(int|float $value = 100): void
    {
        foreach (['orders', 'accounts_receivable', 'expenses', 'payments', 'customers', 'products', 'inventory', 'purchase_orders'] as $table) {
            $builder = Mockery::mock(Builder::class);
            $builder->shouldReceive('clone', 'where', 'whereBetween', 'whereColumn', 'selectRaw')->andReturnSelf();
            $builder->shouldReceive('sum', 'value')->andReturn($value);
            $builder->shouldReceive('count')->andReturn((int) $value);
            $this->connection->shouldReceive('table')->with($table)->andReturn($builder);
        }
    }
}
