<?php

namespace App\Services\Schema;

use App\DataTransferObjects\Schema\SchemaContext;

/**
 * 讀取租戶 schema metadata 的 contract。
 *
 * QueryEngine 和 SchemaFieldController 透過此 interface 取得 schema，
 * 不綁定資料來源（config fixture 或 tenant DB）。
 *
 * 實作：
 * - ConfigSchemaIntrospector：從 config/schema_fixtures.php 讀（測試 / Phase 1 spike 用）
 * - DatabaseSchemaIntrospector：從 tenant DB 的 schema_metadata 表讀（production 用）
 */
interface SchemaIntrospector
{
    /**
     * 取得指定租戶的 schema metadata。
     *
     * @throws \RuntimeException 當 schema 無法取得時（找不到 fixture / DB 連線失敗）
     */
    public function introspect(int $tenantId): SchemaContext;
}
