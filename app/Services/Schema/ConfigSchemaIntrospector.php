<?php

namespace App\Services\Schema;

use App\DataTransferObjects\Schema\ColumnMetadata;
use App\DataTransferObjects\Schema\SchemaContext;
use App\DataTransferObjects\Schema\TableMetadata;
use App\Repositories\Contracts\SchemaFieldRestrictionRepositoryInterface;
use Illuminate\Contracts\Config\Repository;
use RuntimeException;

/**
 * 從 config/schema_fixtures.php 讀取 schema metadata。
 *
 * 測試和 Phase 1 spike 使用。正式環境改用 DatabaseSchemaIntrospector。
 *
 * US-7：config fixture 定義的 restricted 為預設值，DB（schema_field_restrictions 表）
 * 的覆寫優先。管理員透過 admin API toggle 的結果存在 DB。
 */
final class ConfigSchemaIntrospector implements SchemaIntrospector
{
    /** @var array<int, SchemaContext> */
    private array $cache = [];

    public function __construct(
        private readonly Repository $config,
        private readonly SchemaFieldRestrictionRepositoryInterface $restrictionRepo,
    ) {}

    public function introspect(int $tenantId): SchemaContext
    {
        if (isset($this->cache[$tenantId])) {
            return $this->cache[$tenantId];
        }

        $fixture = $this->config->get("schema_fixtures.tenants.{$tenantId}");

        if (! is_array($fixture)) {
            throw new RuntimeException("找不到租戶 {$tenantId} 的 schema fixture，請至 config/schema_fixtures.php 補上");
        }

        $restrictions = $this->loadRestrictions($tenantId);

        return $this->cache[$tenantId] = $this->hydrate($fixture, $restrictions);
    }

    /**
     * @return array<string, bool>
     */
    private function loadRestrictions(int $tenantId): array
    {
        $map = [];

        foreach ($this->restrictionRepo->allOverridesForTenant($tenantId) as $row) {
            $map["{$row->table_name}.{$row->column_name}"] = $row->is_restricted;
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $fixture
     * @param  array<string, bool>  $restrictions
     */
    private function hydrate(array $fixture, array $restrictions): SchemaContext
    {
        /** @var list<array<string, mixed>> $tableDefs */
        $tableDefs = array_values($fixture['tables'] ?? []);

        $tables = array_map(
            fn (array $tableData): TableMetadata => $this->hydrateTable($tableData, $restrictions),
            $tableDefs,
        );

        return new SchemaContext(
            tables: array_values($tables),
            domainContext: isset($fixture['domain_context']) ? (string) $fixture['domain_context'] : null,
        );
    }

    /**
     * @param  array<string, mixed>  $tableData
     * @param  array<string, bool>  $restrictions
     */
    private function hydrateTable(array $tableData, array $restrictions): TableMetadata
    {
        $tableName = (string) $tableData['name'];

        /** @var list<array<string, mixed>> $columnDefs */
        $columnDefs = array_values($tableData['columns'] ?? []);

        $columns = array_map(
            function (array $columnData) use ($tableName, $restrictions): ColumnMetadata {
                $colName = (string) $columnData['name'];
                $key = "{$tableName}.{$colName}";

                $restricted = $restrictions[$key] ?? (bool) ($columnData['restricted'] ?? false);

                return new ColumnMetadata(
                    name: $colName,
                    type: (string) $columnData['type'],
                    displayName: (string) $columnData['display_name'],
                    description: isset($columnData['description']) ? (string) $columnData['description'] : null,
                    restricted: $restricted,
                );
            },
            $columnDefs,
        );

        return new TableMetadata(
            name: $tableName,
            displayName: (string) $tableData['display_name'],
            columns: array_values($columns),
        );
    }
}
