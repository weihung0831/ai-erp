<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSchemaFieldRequest;
use App\Repositories\Contracts\SchemaFieldRestrictionRepositoryInterface;
use App\Services\Schema\SchemaIntrospector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 管理員 schema 欄位管理（US-7 敏感欄位保護）。
 *
 * GET   /api/admin/schema-fields              — 列出所有欄位（含 restricted 狀態）
 * PATCH /api/admin/schema-fields/{table}/{column} — 切換欄位 restricted 狀態
 */
class SchemaFieldController extends Controller
{
    public function index(Request $request, SchemaIntrospector $introspector): JsonResponse
    {
        $schema = $introspector->introspect($request->user()->tenant_id);

        $fields = [];
        foreach ($schema->tables as $table) {
            foreach ($table->columns as $column) {
                $fields[] = [
                    'table' => $table->name,
                    'table_display_name' => $table->displayName,
                    'column' => $column->name,
                    'column_display_name' => $column->displayName,
                    'type' => $column->type,
                    'is_restricted' => $column->restricted,
                ];
            }
        }

        return response()->json(['data' => $fields]);
    }

    public function update(
        UpdateSchemaFieldRequest $request,
        string $table,
        string $column,
        SchemaIntrospector $introspector,
        SchemaFieldRestrictionRepositoryInterface $repo,
    ): JsonResponse {
        $tenantId = $request->user()->tenant_id;

        // 確認欄位存在於 schema fixture
        $schema = $introspector->introspect($tenantId);
        $tableMetadata = $schema->findTable($table);

        if ($tableMetadata === null || $tableMetadata->findColumn($column) === null) {
            return response()->json(['message' => '欄位不存在'], 404);
        }

        $restriction = $repo->toggle(
            tenantId: $tenantId,
            tableName: $table,
            columnName: $column,
            isRestricted: $request->validated('is_restricted'),
        );

        return response()->json([
            'data' => [
                'table' => $table,
                'column' => $column,
                'is_restricted' => $restriction->is_restricted,
            ],
        ]);
    }
}
