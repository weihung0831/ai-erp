<?php

namespace Tests\Unit\Services\Schema;

use App\DataTransferObjects\Schema\SchemaContext;
use App\DataTransferObjects\Schema\TableMetadata;
use App\Repositories\Contracts\SchemaFieldRestrictionRepositoryInterface;
use App\Services\Schema\ConfigSchemaIntrospector;
use Illuminate\Config\Repository;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SchemaIntrospectorTest extends TestCase
{
    private function emptyRestrictionRepo(): SchemaFieldRestrictionRepositoryInterface
    {
        $repo = $this->createMock(SchemaFieldRestrictionRepositoryInterface::class);
        $repo->method('allOverridesForTenant')->willReturn(new Collection);

        return $repo;
    }

    public function test_hydrates_schema_context_from_config_fixture(): void
    {
        $introspector = new ConfigSchemaIntrospector(new Repository([
            'schema_fixtures' => [
                'tenants' => [
                    42 => [
                        'domain_context' => '餐飲業',
                        'tables' => [
                            [
                                'name' => 'orders',
                                'display_name' => '訂單',
                                'columns' => [
                                    ['name' => 'id', 'type' => 'int', 'display_name' => '訂單編號'],
                                    [
                                        'name' => 'total_amount',
                                        'type' => 'decimal',
                                        'display_name' => '訂單金額',
                                        'description' => '含稅總價',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]), $this->emptyRestrictionRepo());

        $context = $introspector->introspect(42);

        $this->assertInstanceOf(SchemaContext::class, $context);
        $this->assertSame('餐飲業', $context->domainContext);
        $this->assertCount(1, $context->tables);

        $orders = $context->tables[0];
        $this->assertInstanceOf(TableMetadata::class, $orders);
        $this->assertSame('orders', $orders->name);
        $this->assertSame('訂單', $orders->displayName);
        $this->assertCount(2, $orders->columns);
        $this->assertSame(['id', 'total_amount'], $orders->columnNames());

        $totalAmount = $orders->findColumn('total_amount');
        $this->assertNotNull($totalAmount);
        $this->assertSame('decimal', $totalAmount->type);
        $this->assertSame('訂單金額', $totalAmount->displayName);
        $this->assertSame('含稅總價', $totalAmount->description);
        $this->assertFalse($totalAmount->restricted);
    }

    public function test_preserves_restricted_flag_for_sensitive_columns(): void
    {
        $introspector = new ConfigSchemaIntrospector(new Repository([
            'schema_fixtures' => [
                'tenants' => [
                    1 => [
                        'tables' => [
                            [
                                'name' => 'employees',
                                'display_name' => '員工',
                                'columns' => [
                                    [
                                        'name' => 'salary',
                                        'type' => 'decimal',
                                        'display_name' => '薪資',
                                        'restricted' => true,
                                    ],
                                    [
                                        'name' => 'name',
                                        'type' => 'varchar',
                                        'display_name' => '姓名',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]), $this->emptyRestrictionRepo());

        $context = $introspector->introspect(1);
        $employees = $context->findTable('employees');

        $this->assertNotNull($employees);
        $this->assertTrue($employees->findColumn('salary')->restricted);
        $this->assertFalse($employees->findColumn('name')->restricted);
    }

    public function test_domain_context_is_null_when_fixture_omits_it(): void
    {
        $introspector = new ConfigSchemaIntrospector(new Repository([
            'schema_fixtures' => ['tenants' => [1 => ['tables' => []]]],
        ]), $this->emptyRestrictionRepo());

        $context = $introspector->introspect(1);

        $this->assertNull($context->domainContext);
        $this->assertSame([], $context->tables);
    }

    public function test_throws_when_tenant_fixture_not_found(): void
    {
        $introspector = new ConfigSchemaIntrospector(new Repository([
            'schema_fixtures' => ['tenants' => []],
        ]), $this->emptyRestrictionRepo());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/找不到租戶 99 的 schema fixture/');

        $introspector->introspect(99);
    }

    public function test_loads_real_default_tenant_1_fixture_from_disk(): void
    {
        // 驗證 config/schema_fixtures.php 實體檔案可以被正確 hydrate——
        // 這層測試同時保證 fixture 檔案 shape 不會因筆誤漂掉。
        // fixture 的 orders / customers 定義必須對齊 DemoSeeder 建立的真實 DB schema。
        $config = new Repository([
            'schema_fixtures' => require __DIR__.'/../../../../config/schema_fixtures.php',
        ]);

        $context = (new ConfigSchemaIntrospector($config, $this->emptyRestrictionRepo()))->introspect(1);

        $this->assertSame('餐飲業', $context->domainContext);
        $this->assertContains('orders', $context->tableNames());
        $this->assertContains('customers', $context->tableNames());

        $totalAmount = $context->findTable('orders')?->findColumn('total_amount');
        $this->assertNotNull($totalAmount);
        $this->assertSame('decimal', $totalAmount->type);
    }
}
