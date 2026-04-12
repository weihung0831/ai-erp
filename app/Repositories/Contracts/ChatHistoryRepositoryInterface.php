<?php

namespace App\Repositories\Contracts;

use App\DataTransferObjects\Chat\ChatQueryResult;
use App\Models\ChatHistory;
use App\Models\Conversation;
use Illuminate\Support\Collection;

interface ChatHistoryRepositoryInterface
{
    /**
     * 建立新對話。
     */
    public function createConversation(int $userId, int $tenantId, string $uuid, string $title): Conversation;

    /**
     * 透過前端的 UUID 查 conversation，限定 user + tenant 歸屬。
     */
    public function findConversationByUuid(string $uuid, int $userId, int $tenantId): ?Conversation;

    /**
     * 儲存一筆 turn 並更新 conversation 計數器。
     */
    public function createTurn(Conversation $conversation, string $message, ChatQueryResult $result): ChatHistory;

    /**
     * 載入指定 conversation 的最近 N 輪，轉成 LLM messages 格式。
     *
     * @return list<array{role: string, content: string}>
     */
    public function toConversationHistory(Conversation $conversation, int $maxTurns = 10): array;

    /**
     * 取得使用者的對話清單，按最後活動時間倒序。
     *
     * @return Collection<int, Conversation>
     */
    public function listConversations(int $userId, int $tenantId, int $limit = 50): Collection;

    /**
     * 載入指定 conversation 的所有 turns，按時間正序。
     *
     * @return Collection<int, ChatHistory>
     */
    public function findMessages(Conversation $conversation): Collection;

    /**
     * 刪除指定對話及其所有 turns（cascade）。
     */
    public function deleteConversation(Conversation $conversation): void;
}
