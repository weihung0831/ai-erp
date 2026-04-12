<?php

namespace App\Repositories\Contracts;

use App\Models\ChatHistory;
use App\Models\QueryLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface QueryLogRepositoryInterface
{
    /**
     * 從 ChatHistory turn 建立一筆 query log。
     */
    public function createFromTurn(ChatHistory $turn, int $tenantId, int $userId): QueryLog;

    /**
     * 管理員列出查詢日誌（分頁 + 篩選）。
     *
     * @param  array{
     *     user_id?: int,
     *     date_from?: string,
     *     date_to?: string,
     *     is_correct?: bool|null,
     * }  $filters
     * @return LengthAwarePaginator<int, QueryLog>
     */
    public function paginate(int $tenantId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * 標記查詢正確或錯誤（準確率追蹤）。
     */
    public function markAccuracy(int $id, int $tenantId, bool $isCorrect): QueryLog;
}
