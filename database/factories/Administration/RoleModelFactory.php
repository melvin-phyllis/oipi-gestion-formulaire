<?php

namespace Database\Factories\Administration;

use App\Models\Administration\RoleModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RoleModel>
 */
class RoleModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_code' => 'TEST_'.Str::upper(Str::random(12)),
            'role_name' => fake()->unique()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'is_system' => false,
            'is_active' => true,
        ];
    }
}
