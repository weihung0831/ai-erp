<?php

namespace App\Repositories\Eloquent;

use App\DataTransferObjects\Chat\ChatQueryResult;
use App\Models\ChatHistory;
use App\Models\Conversation;
use App\Repositories\Contracts\ChatHistoryRepositoryInterface;
use Illuminate\Support\Collection;

class ChatHistoryRepository implements ChatHistoryRepositoryInterface
{
    public function createConversation(int $userId, int $tenantId, string $uuid, string $title): Conversation
    {
        return Conversation::query()->create([
            'uuid' => $uuid,
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'title' => mb_substr($title, 0, 50),
            'message_count' => 0,
            'last_active_at' => now(),
        ]);
    }

    public function findConversationByUuid(string $uuid, int $userId, int $tenantId): ?Conversation
    {
        return Conversation::query()
            ->where('uuid', $uuid)
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function createTurn(Conversation $conversation, string $message, ChatQueryResult $result): ChatHistory
    {
        $turn = $conversation->messages()->create([
            'message' => $message,
            'response' => $result->reply,
            'response_type' => $result->type->value,
            'response_data' => $result->data,
            'sql_generated' => $result->sql,
            'confidence' => $result->confidence,
            'tokens_used' => $result->tokensUsed,
        ]);

        $conversation->increment('message_count', 1, ['last_active_at' => now()]);

        return $turn;
    }

    public function toConversationHistory(Conversation $conversation, int $maxTurns = 10): array
    {
        $turns = $conversation->messages()
            ->orderByDesc('created_at')
            ->limit($maxTurns)
            ->get()
            ->reverse()
            ->values();

        $messages = [];
        foreach ($turns as $turn) {
            $messages[] = ['role' => 'user', 'content' => $turn->message];
            $messages[] = ['role' => 'assistant', 'content' => $turn->response];
        }

        return $messages;
    }

    public function listConversations(int $userId, int $tenantId, int $limit = 50): Collection
    {
        return Conversation::query()
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->orderByDesc('last_active_at')
            ->limit($limit)
            ->get();
    }

    public function findMessages(Conversation $conversation): Collection
    {
        return $conversation->messages()
            ->orderBy('created_at')
            ->get();
    }

    public function deleteConversation(Conversation $conversation): void
    {
        $conversation->delete();
    }
}
