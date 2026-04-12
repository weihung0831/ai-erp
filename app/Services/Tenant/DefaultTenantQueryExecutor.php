<?php

namespace App\Services\Tenant;

use Illuminate\Database\DatabaseManager;

/**
 * Production 實作：用 Laravel DatabaseManager 在租戶 DB 上執行 SQL。
 *
 * 連線名稱由 TenantManager::connectionName() 統一管理，格式為 `tenant_{id}`。
 * TenantMiddleware 會在 request 開始時呼叫 TenantManager::switchTo() 動態
 * 註冊連線，此處直接使用。
 *
 * stdClass → array 的轉換在這層做，讓 QueryEngine 以 associative array
 * 視角處理結果，不需依賴 Laravel 的 stdClass row 型別。
 */
final class DefaultTenantQueryExecutor implements TenantQueryExecutor
{
    public function __construct(private readonly DatabaseManager $db) {}

    public function execute(int $tenantId, string $sql): array
    {
        $connectionName = TenantManager::connectionName($tenantId);

        $rows = $this->db->connection($connectionName)->select($sql);

        return array_values(array_map(
            static fn (object $row): array => (array) $row,
            $rows,
        ));
    }
}
