<?php

namespace App\DataTransferObjects\Chat;

use App\Enums\ChatResponseType;

/**
 * QueryEngine 的輸出 DTO。ChatController 會呼叫 toArray() 轉成 JSON response。
 *
 * sql 欄位一律保留原始 SQL，由 controller / presenter 決定要不要對前端揭露
 * （中信心度 0.70-0.95 顯示 SQL preview，高信心不顯示，低信心根本不執行）。
 */
final readonly class ChatQueryResult
{
    /**
     * @param  array<string, mixed>  $data  結構化資料，shape 依 type 而定：
     *                                      numeric: ['value' => int|float, 'value_format' => 'currency'|'count']
     *                                      table: ['headers' => list<string>, 'rows' => list<list<mixed>>, 'truncated' => bool]
     *                                      — rows 的 cell 順序對齊 headers 位置；truncated 為 true 代表原始結果 > MAX_TABLE_ROWS 被截斷
     *                                      clarification / error: 空陣列
     *                                      `reply` 欄位是給使用者看的完整句子，data 只是後端結構化副本。
     */
    public function __construct(
        public string $reply,
        public float $confidence,
        public ChatResponseType $type,
        public array $data,
        public ?string $sql = null,
        public int $tokensUsed = 0,
    ) {}

    /**
     * @return array{
     *     reply: string,
     *     confidence: float,
     *     type: string,
     *     data: array<string, mixed>,
     *     sql: ?string,
     *     tokens_used: int
     * }
     */
    public function toArray(): array
    {
        return [
            'reply' => $this->reply,
            'confidence' => $this->confidence,
            'type' => $this->type->value,
            'data' => $this->data,
            'sql' => $this->sql,
            'tokens_used' => $this->tokensUsed,
        ];
    }
}
