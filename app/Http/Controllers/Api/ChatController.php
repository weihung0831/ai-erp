<?php

namespace App\Http\Controllers\Api;

use App\DataTransferObjects\Chat\ChatQueryInput;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\ChatRequest;
use App\Services\Ai\QueryEngine;
use Illuminate\Http\JsonResponse;

/**
 * POST /api/chat — 把使用者訊息餵給 Query Engine，回傳結構化結果。
 *
 * Thin controller：只做 FormRequest → DTO → Service → JSON 的轉換，
 * 所有業務邏輯和錯誤處理都在 QueryEngine 內。
 *
 * 目前回傳 ChatQueryResult::toArray() 的完整 payload，包含 `sql` 欄位。
 * US-4 實作時會在此處依信心度決定是否揭露 sql preview 給前端。
 */
class ChatController extends Controller
{
    public function handle(ChatRequest $request, QueryEngine $queryEngine): JsonResponse
    {
        $user = $request->user();

        $result = $queryEngine->handle(new ChatQueryInput(
            message: (string) $request->input('message'),
            tenantId: (int) $user->tenant_id,
        ));

        return response()->json($result->toArray());
    }
}
