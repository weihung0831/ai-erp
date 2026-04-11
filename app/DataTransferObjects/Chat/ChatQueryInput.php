<?php

namespace App\DataTransferObjects\Chat;

/**
 * QueryEngine 的輸入 DTO。由 ChatController 從 FormRequest 轉換後傳入。
 *
 * conversationHistory 預留給 US-3 多輪對話，US-1 階段永遠是空陣列。
 * tenantId 顯式傳入（而非從 TenantManager 取）以確保 QueryEngine
 * 的 unit test 可以不需設定 request-scoped 容器。
 */
final readonly class ChatQueryInput
{
    /**
     * @param  list<array{role: string, content: string}>  $conversationHistory  最多 10 輪，由呼叫端裁切
     */
    public function __construct(
        public string $message,
        public int $tenantId,
        public array $conversationHistory = [],
    ) {}
}
