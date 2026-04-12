<?php

namespace App\Models;

use App\Enums\ChatResponseType;
use Database\Factories\ChatHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'conversation_id',
    'message',
    'response',
    'response_type',
    'response_data',
    'sql_generated',
    'confidence',
    'tokens_used',
])]
class ChatHistory extends Model
{
    /** @use HasFactory<ChatHistoryFactory> */
    use HasFactory;

    /** chat_histories 只有 created_at，不需要 updated_at。 */
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'response_type' => ChatResponseType::class,
            'response_data' => 'array',
            'confidence' => 'float',
            'tokens_used' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
