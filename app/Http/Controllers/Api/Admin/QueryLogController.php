<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QueryLogFilterRequest;
use App\Http\Requests\Admin\UpdateQueryLogRequest;
use App\Repositories\Contracts\QueryLogRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

/**
 * 管理員查詢日誌（US-8）。
 *
 * GET   /api/admin/query-logs      — 列出所有查詢紀錄（可按日期/使用者篩選）
 * PATCH /api/admin/query-logs/{id} — 標記查詢正確或錯誤（準確率追蹤）
 */
class QueryLogController extends Controller
{
    public function index(QueryLogFilterRequest $request, QueryLogRepositoryInterface $repo): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 20);

        $logs = $repo->paginate($request->user()->tenant_id, $validated, $perPage);

        return response()->json($logs);
    }

    public function update(UpdateQueryLogRequest $request, int $id, QueryLogRepositoryInterface $repo): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        try {
            $log = $repo->markAccuracy($id, $tenantId, $request->validated('is_correct'));
        } catch (ModelNotFoundException) {
            return response()->json(['message' => '查詢日誌不存在'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $log->id,
                'is_correct' => $log->is_correct,
                'reviewed_at' => $log->reviewed_at->toIso8601String(),
            ],
        ]);
    }
}
