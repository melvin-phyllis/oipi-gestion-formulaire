<?php

namespace Database\Factories\Registry;

use App\Enums\Registry\RegistryImportStatus;
use App\Models\Administration\UserModel;
use App\Models\Registry\MissingFormImportModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MissingFormImportModel>
 */
final class MissingFormImportModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => RegistryImportStatus::Pending,
            'disk' => 'local',
            'file_path' => 'imports/registry/'.fake()->uuid().'.csv',
            'original_file_name' => 'formulaires.csv',
            'size_bytes' => 1024,
            'created_by_user_code_unique' => UserModel::factory(),
        ];
    }
}
