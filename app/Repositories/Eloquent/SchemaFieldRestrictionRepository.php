<?php

namespace App\Repositories\Eloquent;

use App\Models\SchemaFieldRestriction;
use App\Repositories\Contracts\SchemaFieldRestrictionRepositoryInterface;
use Illuminate\Support\Collection;

class SchemaFieldRestrictionRepository implements SchemaFieldRestrictionRepositoryInterface
{
    public function allOverridesForTenant(int $tenantId): Collection
    {
        return SchemaFieldRestriction::query()
            ->where('tenant_id', $tenantId)
            ->get();
    }

    public function toggle(int $tenantId, string $tableName, string $columnName, bool $isRestricted): SchemaFieldRestriction
    {
        return SchemaFieldRestriction::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'table_name' => $tableName,
                'column_name' => $columnName,
            ],
            ['is_restricted' => $isRestricted],
        );
    }
}
