<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuickActionRequest;
use App\Repositories\Contracts\QuickActionRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 管理員快捷按鈕 CRUD。
 *
 * GET    /api/admin/quick-actions       — 列表（含 inactive）
 * POST   /api/admin/quick-actions       — 新增
 * DELETE /api/admin/quick-actions/{id}  — 刪除
 */
class QuickActionController extends Controller
{
    public function index(Request $request, QuickActionRepositoryInterface $repo): JsonResponse
    {
        $actions = $repo->allForTenant($request->user()->tenant_id)
            ->map(fn ($qa) => [
                'id' => $qa->id,
                'label' => $qa->label,
                'prompt' => $qa->prompt,
                'sort_order' => $qa->sort_order,
                'is_active' => $qa->is_active,
            ]);

        return response()->json(['data' => $actions]);
    }

    public function store(StoreQuickActionRequest $request, QuickActionRepositoryInterface $repo): JsonResponse
    {
        $qa = $repo->create(
            tenantId: $request->user()->tenant_id,
            label: $request->validated('label'),
            prompt: $request->validated('prompt'),
            sortOrder: $request->validated('sort_order', 0),
        );

        return response()->json([
            'data' => [
                'id' => $qa->id,
                'label' => $qa->label,
                'prompt' => $qa->prompt,
                'sort_order' => $qa->sort_order,
                'is_active' => $qa->is_active,
            ],
        ], 201);
    }

    public function destroy(Request $request, int $id, QuickActionRepositoryInterface $repo): Response
    {
        $deleted = $repo->deleteForTenant($id, $request->user()->tenant_id);

        if (! $deleted) {
            return response()->json(['message' => '快捷按鈕不存在或無權刪除'], 404);
        }

        return response()->noContent();
    }
}
