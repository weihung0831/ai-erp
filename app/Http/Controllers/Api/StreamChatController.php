<?php

namespace App\Http\Controllers\Api;

use App\DataTransferObjects\Chat\ChatQueryInput;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\ChatRequest;
use App\Repositories\Contracts\ChatHistoryRepositoryInterface;
use App\Services\Ai\QueryEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * POST /api/chat/stream — SSE 串流版聊天端點。
 *
 * 與 ChatController 邏輯相同（驗證 → 載對話 → QueryEngine → 存 turn），
 * 差別在於使用 QueryEngine::handleStream() 逐步 yield SSE 事件。
 *
 * SSE 事件格式：
 *   event: thinking   — LLM 開始處理，前端顯示 typing indicator
 *   event: text       — 文字 delta（clarification / 閒聊時逐字顯示）
 *   event: result     — 最終結構化結果（同 /api/chat 的 JSON）
 *   event: done       — 串流結束
 */
class StreamChatController extends Controller
{
    private const int MAX_TURNS_PER_CONVERSATION = 50;

    private const int MAX_CONTEXT_TURNS = 10;

    public function handle(
        ChatRequest $request,
        QueryEngine $queryEngine,
        ChatHistoryRepositoryInterface $chatHistory,
    ): StreamedResponse|JsonResponse {
        $user = $request->user();
        $conversationUuid = $request->input('conversation_id');
        $message = (string) $request->input('message');

        if ($conversationUuid !== null) {
            $conversation = $chatHistory->findConversationByUuid($conversationUuid, $user->id, $user->tenant_id);

            if ($conversation === null) {
                return response()->json(['message' => '對話不存在或無權存取'], 403);
            }

            if ($conversation->message_count >= self::MAX_TURNS_PER_CONVERSATION) {
                return response()->json(['message' => '此對話已達 50 輪上限，請開啟新對話'], 422);
            }

            $history = $chatHistory->toConversationHistory($conversation, self::MAX_CONTEXT_TURNS);
        } else {
            $conversationUuid = Str::uuid()->toString();
            $conversation = $chatHistory->createConversation(
                $user->id,
                $user->tenant_id,
                $conversationUuid,
                $message,
            );
            $history = [];
        }

        $input = new ChatQueryInput(
            message: $message,
            tenantId: (int) $user->tenant_id,
            conversationHistory: $history,
        );

        return response()->stream(function () use ($queryEngine, $input, $chatHistory, $conversation, $conversationUuid, $message): void {
            // 丟棄 output buffer（不 flush，避免 middleware 殘留內容污染 SSE）
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            $generator = $queryEngine->handleStream($input);

            foreach ($generator as $event) {
                if ($event['event'] === 'result') {
                    $event['data']['conversation_id'] = $conversationUuid;
                }
                $this->sendEvent($event['event'], $event['data']);
            }

            // Generator 結束，取最終 ChatQueryResult 做持久化。
            // SSE 已送完，DB 失敗不應讓 client 掛住——log 後仍送 done event。
            try {
                $result = $generator->getReturn();
                $turn = $chatHistory->createTurn($conversation, $message, $result);
            } catch (\Throwable $e) {
                report($e);
            }

            $this->sendEvent('done', (object) []);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function sendEvent(string $event, mixed $data): void
    {
        echo "event: {$event}\ndata: ".json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n\n";
        flush();
    }
}
