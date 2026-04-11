<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slug = Str::lower(Str::random(8));

        return [
            'name' => fake()->company(),
            'db_name' => "ai_erp_tenant_{$slug}",
            'industry' => fake()->randomElement(['restaurant', 'manufacturing', 'trading', 'retail']),
        ];
    }
}
