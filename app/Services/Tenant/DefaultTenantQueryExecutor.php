<?php

namespace App\Services\Tenant;

use Illuminate\Database\DatabaseManager;

/**
 * Production 實作：用 Laravel DatabaseManager 執行 SQL。
 *
 * **Phase 1 stub：** 永遠走預設連線。Phase 1 收尾時改為
 * `$this->db->connection("tenant_{$tenantId}")`，並與 TenantManager 的
 * 真實連線切換邏輯同步完成。
 *
 * stdClass → array 的轉換在這層做，讓 QueryEngine 以 associative array
 * 視角處理結果，不需依賴 Laravel 的 stdClass row 型別。
 */
final class DefaultTenantQueryExecutor implements TenantQueryExecutor
{
    public function __construct(private readonly DatabaseManager $db) {}

    public function execute(int $tenantId, string $sql): array
    {
        $rows = $this->db->connection()->select($sql);

        return array_values(array_map(
            static fn (object $row): array => (array) $row,
            $rows,
        ));
    }
}
