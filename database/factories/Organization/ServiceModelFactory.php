<?php

namespace Database\Factories\Organization;

use App\Models\Organization\DirectorateModel;
use App\Models\Organization\ServiceModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ServiceModel>
 */
class ServiceModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'directorate_code_unique' => fn (): string => DirectorateModel::factory()
                ->create()
                ->directorate_code_unique,
            'service_code' => 'TEST_'.Str::upper(Str::random(10)),
            'abbreviation' => Str::upper(Str::random(5)),
            'service_name' => fake()->unique()->company(),
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
