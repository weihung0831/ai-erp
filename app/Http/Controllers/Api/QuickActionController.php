<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\QuickActionRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 使用者端快捷按鈕。
 *
 * GET /api/quick-actions — 顯示在聊天頁的啟用中快捷按鈕
 */
class QuickActionController extends Controller
{
    public function index(Request $request, QuickActionRepositoryInterface $repo): JsonResponse
    {
        $actions = $repo->activeForTenant($request->user()->tenant_id)
            ->map(fn ($qa) => [
                'id' => $qa->id,
                'label' => $qa->label,
                'prompt' => $qa->prompt,
            ]);

        return response()->json(['data' => $actions]);
    }
}
