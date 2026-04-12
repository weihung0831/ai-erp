<?php

namespace App\Services\Schema;

use App\DataTransferObjects\Schema\ColumnMetadata;
use App\DataTransferObjects\Schema\SchemaContext;
use App\DataTransferObjects\Schema\TableMetadata;
use App\Repositories\Contracts\SchemaFieldRestrictionRepositoryInterface;
use App\Services\Tenant\TenantManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * 從 tenant DB 的 schema_metadata 表讀取 schema metadata（production 用）。
 *
 * schema_metadata 表的欄位：
 *   - table_name: 資料表名稱
 *   - column_name: 欄位名稱（NULL = table 層級 metadata）
 *   - display_name: 中文顯示名稱
 *   - data_type: 欄位型別
 *   - description: 補充說明
 *   - is_restricted: 敏感欄位標記
 *
 * domain_context 從主 DB 的 tenants.industry 取得。
 *
 * US-7 restriction 覆寫：schema_field_restrictions（主 DB）的覆寫優先於
 * schema_metadata 的 is_restricted 預設值。
 */
final class DatabaseSchemaIntrospector implements SchemaIntrospector
{
    /** @var array<int, SchemaContext> */
    private array $cache = [];

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly SchemaFieldRestrictionRepositoryInterface $restrictionRepo,
        private readonly TenantManager $tenantManager,
    ) {}

    public function introspect(int $tenantId): SchemaContext
    {
        if (isset($this->cache[$tenantId])) {
            return $this->cache[$tenantId];
        }

        $tenant = $this->tenantManager->current();
        if ($tenant === null || $tenant->id !== $tenantId) {
            throw new RuntimeException("租戶 {$tenantId} 的上下文尚未設定，請確認 TenantMiddleware 已執行");
        }

        $connectionName = TenantManager::connectionName($tenantId);
        $rows = $this->db->connection($connectionName)
            ->table('schema_metadata')
            ->orderBy('table_name')
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            throw new RuntimeException("租戶 {$tenantId} 的 schema_metadata 為空，請先執行 seeder");
        }

        $restrictions = $this->loadRestrictions($tenantId);
        $domainContext = $tenant->domainContextLabel();

        return $this->cache[$tenantId] = $this->hydrate($rows, $restrictions, $domainContext);
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
     * @param  Collection<int, object>  $rows
     * @param  array<string, bool>  $restrictions
     */
    private function hydrate($rows, array $restrictions, ?string $domainContext): SchemaContext
    {
        // 分組：table-level rows (column_name IS NULL) 和 column-level rows
        $tableDisplayNames = [];
        $columnsByTable = [];

        foreach ($rows as $row) {
            $tableName = $row->table_name;

            if ($row->column_name === null) {
                // Table-level metadata
                $tableDisplayNames[$tableName] = $row->display_name;
            } else {
                // Column-level metadata
                $key = "{$tableName}.{$row->column_name}";
                $restricted = $restrictions[$key] ?? (bool) $row->is_restricted;

                $columnsByTable[$tableName][] = new ColumnMetadata(
                    name: $row->column_name,
                    type: $row->data_type ?? 'varchar',
                    displayName: $row->display_name,
                    description: $row->description,
                    restricted: $restricted,
                );
            }
        }

        $tables = [];
        foreach ($columnsByTable as $tableName => $columns) {
            $tables[] = new TableMetadata(
                name: $tableName,
                displayName: $tableDisplayNames[$tableName] ?? $tableName,
                columns: $columns,
            );
        }

        return new SchemaContext(
            tables: $tables,
            domainContext: $domainContext,
        );
    }
}
