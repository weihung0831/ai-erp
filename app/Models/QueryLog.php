<?php

namespace App\Models;

use Database\Factories\QueryLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id',
    'user_id',
    'chat_history_id',
    'question',
    'reply',
    'sql_executed',
    'confidence',
    'result_hash',
    'tokens_used',
    'is_correct',
    'reviewed_at',
])]
class QueryLog extends Model
{
    /** @use HasFactory<QueryLogFactory> */
    use HasFactory;

    /** query_logs 只有 created_at，不需要 updated_at。 */
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confidence' => 'float',
            'tokens_used' => 'integer',
            'is_correct' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ChatHistory, $this>
     */
    public function chatHistory(): BelongsTo
    {
        return $this->belongsTo(ChatHistory::class);
    }
}
