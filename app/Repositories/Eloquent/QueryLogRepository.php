<?php

namespace App\Repositories\Eloquent;

use App\Models\ChatHistory;
use App\Models\QueryLog;
use App\Repositories\Contracts\QueryLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class QueryLogRepository implements QueryLogRepositoryInterface
{
    public function createFromTurn(ChatHistory $turn, int $tenantId, int $userId): QueryLog
    {
        return QueryLog::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'chat_history_id' => $turn->id,
            'question' => $turn->message,
            'reply' => $turn->response,
            'sql_executed' => $turn->sql_generated,
            'confidence' => $turn->confidence,
            'result_hash' => $turn->sql_generated ? hash('sha256', $turn->sql_generated) : null,
            'tokens_used' => $turn->tokens_used,
        ]);
    }

    public function paginate(int $tenantId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = QueryLog::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at');

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'].' 23:59:59');
        }

        if (array_key_exists('is_correct', $filters)) {
            if ($filters['is_correct'] === null) {
                $query->whereNull('is_correct');
            } else {
                $query->where('is_correct', $filters['is_correct']);
            }
        }

        return $query->paginate($perPage);
    }

    public function markAccuracy(int $id, int $tenantId, bool $isCorrect): QueryLog
    {
        $log = QueryLog::query()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $log->is_correct = $isCorrect;
        $log->reviewed_at = now();
        $log->save();

        return $log;
    }
}
