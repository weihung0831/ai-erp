<?php

namespace Database\Factories;

use App\Models\QuickAction;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuickAction>
 */
class QuickActionFactory extends Factory
{
    private const PRESETS = [
        ['label' => '本月營收', 'prompt' => '這個月營收多少？'],
        ['label' => '庫存狀況', 'prompt' => '目前庫存量前十名的商品有哪些？'],
        ['label' => '應收帳款', 'prompt' => '應收帳款超過 60 天的客戶有哪些？'],
        ['label' => 'Top 10 客戶', 'prompt' => '本月營收前十名的客戶是誰？'],
        ['label' => '本月訂單數', 'prompt' => '這個月總共有幾筆訂單？'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $preset = fake()->randomElement(self::PRESETS);

        return [
            'tenant_id' => Tenant::factory(),
            'label' => $preset['label'],
            'prompt' => $preset['prompt'],
            'sort_order' => fake()->numberBetween(0, 10),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }
}
