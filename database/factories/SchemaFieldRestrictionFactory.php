<?php

namespace Database\Factories;

use App\Models\SchemaFieldRestriction;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchemaFieldRestriction>
 */
class SchemaFieldRestrictionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'table_name' => fake()->randomElement(['orders', 'customers']),
            'column_name' => fake()->word(),
            'is_restricted' => true,
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    public function unrestricted(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_restricted' => false,
        ]);
    }
}
