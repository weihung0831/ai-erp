<?php

namespace App\Events;

use App\Models\ChatHistory;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * 聊天查詢完成後觸發，供 LogQueryListener 記錄查詢日誌。
 */
class QueryExecuted
{
    use Dispatchable;

    public function __construct(
        public readonly ChatHistory $turn,
        public readonly int $tenantId,
        public readonly int $userId,
    ) {}
}
