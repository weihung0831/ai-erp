<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid()->toString(),
            'user_id' => User::factory(),
            'tenant_id' => Tenant::factory(),
            'title' => mb_substr(fake()->sentence(), 0, 50),
            'message_count' => 0,
            'last_active_at' => now(),
        ];
    }

    /**
     * 綁定既有 user（同時帶入 tenant_id）。
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
        ]);
    }
}
