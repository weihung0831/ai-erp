<?php

namespace Database\Factories;

use App\Enums\ChatResponseType;
use App\Models\ChatHistory;
use App\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatHistory>
 */
class ChatHistoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'message' => fake()->sentence(),
            'response' => fake()->paragraph(),
            'response_type' => ChatResponseType::Numeric,
            'response_data' => ['value' => fake()->randomNumber(5), 'value_format' => 'currency'],
            'sql_generated' => 'SELECT SUM(amount) FROM orders',
            'confidence' => fake()->randomFloat(2, 0.70, 1.00),
            'tokens_used' => fake()->numberBetween(500, 3000),
        ];
    }

    /**
     * 綁定既有 conversation。
     */
    public function forConversation(Conversation $conversation): static
    {
        return $this->state(fn (array $attributes) => [
            'conversation_id' => $conversation->id,
        ]);
    }

    /**
     * 表格類型回應。
     */
    public function table(): static
    {
        return $this->state(fn (array $attributes) => [
            'response_type' => ChatResponseType::Table,
            'response_data' => [
                'headers' => ['客戶名稱', '金額', '逾期天數'],
                'rows' => [['A 公司', 150000, 65], ['B 公司', 230000, 72]],
                'truncated' => false,
            ],
        ]);
    }

    /**
     * 釐清類型回應（低信心度）。
     */
    public function clarification(): static
    {
        return $this->state(fn (array $attributes) => [
            'response_type' => ChatResponseType::Clarification,
            'response_data' => [],
            'confidence' => fake()->randomFloat(2, 0.30, 0.69),
            'sql_generated' => null,
        ]);
    }
}
