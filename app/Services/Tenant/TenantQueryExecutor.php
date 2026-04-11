<?php

namespace App\Services\Tenant;

/**
 * 執行 SQL 到指定租戶的 DB 連線。
 *
 * 這個 interface 的存在目的是**讓 QueryEngine 的單元測試完全不打 DB**：
 * 測試注入 FakeTenantQueryExecutor 塞 canned 結果，production 走 Default 實作。
 * SQL 實際執行正確性交給 Golden Test Suite（對真實 MySQL tenant DB）驗證。
 *
 * Phase 1 stub：Default 實作永遠用預設連線（因 TenantManager 尚未實作真實連線切換）。
 * Phase 1 收尾時改為 `connection("tenant_{$tenantId}")`，interface shape 不動。
 */
interface TenantQueryExecutor
{
    /**
     * 對指定租戶執行 SELECT，回傳以關聯陣列表示的資料列。
     *
     * @return list<array<string, mixed>>
     */
    public function execute(int $tenantId, string $sql): array;
}
