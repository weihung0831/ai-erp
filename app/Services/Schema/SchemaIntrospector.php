<?php

namespace App\Services\Schema;

use App\DataTransferObjects\Schema\ColumnMetadata;
use App\DataTransferObjects\Schema\SchemaContext;
use App\DataTransferObjects\Schema\TableMetadata;
use Illuminate\Contracts\Config\Repository;
use RuntimeException;

/**
 * 讀取租戶 schema metadata，提供給 QueryEngine 組 system prompt 使用。
 *
 * **Phase 1 stub：** 目前從 config/schema_fixtures.php 讀（硬 code 的餐飲業範例）。
 * Phase 1 收尾時要改為從 tenant DB 的 schema_metadata 表讀
 * （見 docs/architecture/system-architecture.md 第 242 行）。
 * 換實作時 QueryEngine 不需要動，回傳的 SchemaContext shape 保持一樣。
 *
 * 技術債追蹤：
 * - 改為讀 tenant DB 的 schema_metadata 表
 * - TenantManager 需先實作真實的 DB 連線切換（目前也是 stub）
 */
final class SchemaIntrospector
{
    /**
     * Per-instance 的 SchemaContext cache，避免同一 request 內重複 hydrate
     * 同一 tenant。SchemaIntrospector 被 QueryEngine constructor-inject，同一
     * request 內只會有一個 instance，cache 壽命等於 request 壽命。
     *
     * @var array<int, SchemaContext>
     */
    private array $cache = [];

    public function __construct(private readonly Repository $config) {}

    /**
     * 取得指定租戶的 schema metadata。
     *
     * Phase 1 stub 階段，若 config 中找不到該租戶的 fixture 會 throw，
     * 提醒開發者去補 fixture。改成真實 DB 讀取後，會改為 throw 不同的 exception
     * （例：租戶 DB 連線失敗）。
     *
     * @throws RuntimeException
     */
    public function introspect(int $tenantId): SchemaContext
    {
        if (isset($this->cache[$tenantId])) {
            return $this->cache[$tenantId];
        }

        $fixture = $this->config->get("schema_fixtures.tenants.{$tenantId}");

        if (! is_array($fixture)) {
            throw new RuntimeException("找不到租戶 {$tenantId} 的 schema fixture，請至 config/schema_fixtures.php 補上");
        }

        return $this->cache[$tenantId] = $this->hydrate($fixture);
    }

    /**
     * @param  array<string, mixed>  $fixture
     */
    private function hydrate(array $fixture): SchemaContext
    {
        /** @var list<array<string, mixed>> $tableDefs */
        $tableDefs = array_values($fixture['tables'] ?? []);

        $tables = array_map(
            fn (array $tableData): TableMetadata => $this->hydrateTable($tableData),
            $tableDefs,
        );

        return new SchemaContext(
            tables: array_values($tables),
            domainContext: isset($fixture['domain_context']) ? (string) $fixture['domain_context'] : null,
        );
    }

    /**
     * @param  array<string, mixed>  $tableData
     */
    private function hydrateTable(array $tableData): TableMetadata
    {
        /** @var list<array<string, mixed>> $columnDefs */
        $columnDefs = array_values($tableData['columns'] ?? []);

        $columns = array_map(
            fn (array $columnData): ColumnMetadata => new ColumnMetadata(
                name: (string) $columnData['name'],
                type: (string) $columnData['type'],
                displayName: (string) $columnData['display_name'],
                description: isset($columnData['description']) ? (string) $columnData['description'] : null,
                restricted: (bool) ($columnData['restricted'] ?? false),
            ),
            $columnDefs,
        );

        return new TableMetadata(
            name: (string) $tableData['name'],
            displayName: (string) $tableData['display_name'],
            columns: array_values($columns),
        );
    }
}
