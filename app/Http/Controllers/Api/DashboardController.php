<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Dashboard 指標 API（US-13）。
 *
 * GET /api/dashboard — 取得 Dashboard 指標清單
 */
class DashboardController extends Controller
{
    public function index(Request $request, DashboardService $dashboardService): JsonResponse
    {
        $metrics = $dashboardService->getMetrics($request->user()->tenant_id);

        return response()->json([
            'data' => array_map(fn ($metric) => $metric->toArray(), $metrics),
        ]);
    }
}
