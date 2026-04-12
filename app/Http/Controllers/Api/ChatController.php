<?php

namespace App\Http\Controllers\Api;

use App\DataTransferObjects\Chat\ChatQueryInput;
use App\Events\QueryExecuted;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\ChatRequest;
use App\Repositories\Contracts\ChatHistoryRepositoryInterface;
use App\Services\Ai\QueryEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * POST /api/chat — 把使用者訊息餵給 Query Engine，回傳結構化結果。
 *
 * Thin controller：FormRequest → 載對話歷史 → DTO → Service → 存 turn → JSON。
 * 所有業務邏輯和錯誤處理都在 QueryEngine 內。
 */
class ChatController extends Controller
{
    /** 單次對話最多 50 輪，超過建議開新對話。 */
    private const int MAX_TURNS_PER_CONVERSATION = 50;

    /** 送給 LLM 的對話歷史上限（最近 10 輪）。 */
    private const int MAX_CONTEXT_TURNS = 10;

    public function handle(
        ChatRequest $request,
        QueryEngine $queryEngine,
        ChatHistoryRepositoryInterface $chatHistory,
    ): JsonResponse {
        $user = $request->user();
        $conversationUuid = $request->input('conversation_id');
        $message = (string) $request->input('message');

        if ($conversationUuid !== null) {
            // 接續既有對話
            $conversation = $chatHistory->findConversationByUuid($conversationUuid, $user->id, $user->tenant_id);

            if ($conversation === null) {
                return response()->json(['message' => '對話不存在或無權存取'], 403);
            }

            if ($conversation->message_count >= self::MAX_TURNS_PER_CONVERSATION) {
                return response()->json(['message' => '此對話已達 50 輪上限，請開啟新對話'], 422);
            }

            $history = $chatHistory->toConversationHistory($conversation, self::MAX_CONTEXT_TURNS);
        } else {
            // 開新對話
            $conversationUuid = Str::uuid()->toString();
            $conversation = $chatHistory->createConversation(
                $user->id,
                $user->tenant_id,
                $conversationUuid,
                $message,
            );
            $history = [];
        }

        $result = $queryEngine->handle(new ChatQueryInput(
            message: $message,
            tenantId: (int) $user->tenant_id,
            conversationHistory: $history,
        ));

        $turn = $chatHistory->createTurn($conversation, $message, $result);

        QueryExecuted::dispatch($turn, (int) $user->tenant_id, (int) $user->id);

        return response()->json([
            ...$result->toArray(),
            'conversation_id' => $conversationUuid,
        ]);
    }
}
