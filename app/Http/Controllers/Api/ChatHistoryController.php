<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Repositories\Contracts\ChatHistoryRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 對話歷史 API。
 *
 * GET /api/chat/history           — 對話清單
 * GET /api/chat/history/{uuid}    — 單一對話的所有 messages
 * DELETE /api/chat/history/{uuid} — 刪除對話
 */
class ChatHistoryController extends Controller
{
    public function index(Request $request, ChatHistoryRepositoryInterface $chatHistory): JsonResponse
    {
        $user = $request->user();

        $conversations = $chatHistory->listConversations($user->id, $user->tenant_id)
            ->map(fn ($conv) => [
                'conversation_id' => $conv->uuid,
                'title' => $conv->title,
                'message_count' => $conv->message_count,
                'last_active_at' => $conv->last_active_at->toISOString(),
            ]);

        return response()->json(['data' => $conversations]);
    }

    public function show(
        string $conversationUuid,
        Request $request,
        ChatHistoryRepositoryInterface $chatHistory,
    ): JsonResponse {
        $conversation = $this->resolveConversation($conversationUuid, $request, $chatHistory);

        if ($conversation instanceof JsonResponse) {
            return $conversation;
        }

        $messages = $chatHistory->findMessages($conversation)
            ->map(fn ($turn) => [
                'message' => $turn->message,
                'response' => $turn->response,
                'response_type' => $turn->response_type->value,
                'response_data' => $turn->response_data,
                'confidence' => $turn->confidence,
                'created_at' => $turn->created_at->toISOString(),
            ]);

        return response()->json(['data' => $messages]);
    }

    public function destroy(
        string $conversationUuid,
        Request $request,
        ChatHistoryRepositoryInterface $chatHistory,
    ): JsonResponse {
        $conversation = $this->resolveConversation($conversationUuid, $request, $chatHistory);

        if ($conversation instanceof JsonResponse) {
            return $conversation;
        }

        $chatHistory->deleteConversation($conversation);

        return response()->json(['message' => '對話已刪除']);
    }

    private function resolveConversation(
        string $uuid,
        Request $request,
        ChatHistoryRepositoryInterface $chatHistory,
    ): Conversation|JsonResponse {
        $user = $request->user();
        $conversation = $chatHistory->findConversationByUuid($uuid, $user->id, $user->tenant_id);

        if ($conversation === null) {
            return response()->json(['message' => '對話不存在或無權存取'], 403);
        }

        return $conversation;
    }
}
