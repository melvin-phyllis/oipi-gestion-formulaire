<?php

namespace Database\Factories\Administration;

use App\Models\Administration\PermissionModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PermissionModel>
 */
class PermissionModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'permission_code' => 'test_'.Str::lower(Str::random(12)),
            'group_code' => fake()->randomElement([
                'registry',
                'missions',
                'analytics',
                'administration',
                'audit',
                'backups',
            ]),
            'permission_name' => fake()->unique()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'is_system' => false,
            'is_active' => true,
        ];
    }
}
