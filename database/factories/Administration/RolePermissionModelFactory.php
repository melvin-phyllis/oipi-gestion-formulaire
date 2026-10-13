<?php

namespace Database\Factories\Administration;

use App\Models\Administration\PermissionModel;
use App\Models\Administration\RoleModel;
use App\Models\Administration\RolePermissionModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RolePermissionModel>
 */
class RolePermissionModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_code_unique' => fn (): string => RoleModel::factory()
                ->create()
                ->role_code_unique,
            'permission_code_unique' => fn (): string => PermissionModel::factory()
                ->create()
                ->permission_code_unique,
            'is_active' => true,
        ];
    }
}
