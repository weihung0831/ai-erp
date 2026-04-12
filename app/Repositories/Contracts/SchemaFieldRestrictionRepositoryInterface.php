<?php

namespace App\Repositories\Contracts;

use App\Models\SchemaFieldRestriction;
use Illuminate\Support\Collection;

interface SchemaFieldRestrictionRepositoryInterface
{
    /**
     * 取得該租戶所有欄位限制覆寫（含 restricted=true 和 false）。
     * SchemaIntrospector 需要兩種狀態才能正確覆寫 config fixture 預設值。
     *
     * @return Collection<int, SchemaFieldRestriction>
     */
    public function allOverridesForTenant(int $tenantId): Collection;

    /**
     * 切換指定欄位的 restricted 狀態（upsert）。
     */
    public function toggle(int $tenantId, string $tableName, string $columnName, bool $isRestricted): SchemaFieldRestriction;
}
