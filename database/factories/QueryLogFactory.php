<?php

namespace Database\Factories;

use App\Models\QueryLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QueryLog>
 */
class QueryLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'chat_history_id' => null,
            'question' => fake()->sentence(),
            'reply' => fake()->paragraph(),
            'sql_executed' => 'SELECT SUM(amount) FROM orders',
            'confidence' => fake()->randomFloat(2, 0.70, 1.00),
            'result_hash' => fake()->sha256(),
            'tokens_used' => fake()->numberBetween(500, 3000),
            'is_correct' => null,
            'reviewed_at' => null,
        ];
    }

    /**
     * 綁定既有 tenant + user。
     */
    public function forTenantUser(Tenant $tenant, User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * 已標記為正確。
     */
    public function correct(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_correct' => true,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * 已標記為錯誤。
     */
    public function incorrect(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_correct' => false,
            'reviewed_at' => now(),
        ]);
    }
}
