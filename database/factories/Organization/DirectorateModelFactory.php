<?php

namespace Database\Factories\Organization;

use App\Models\Organization\DirectorateModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DirectorateModel>
 */
class DirectorateModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'directorate_code' => 'TEST_'.Str::upper(Str::random(10)),
            'directorate_name' => fake()->unique()->company(),
            'is_active' => true,
            'display_order' => fake()->numberBetween(1, 999),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
