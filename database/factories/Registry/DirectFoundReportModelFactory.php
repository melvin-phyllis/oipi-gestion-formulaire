<?php

namespace Database\Factories\Registry;

use App\Enums\Registry\FinderType;
use App\Models\Administration\UserModel;
use App\Models\Registry\DirectFoundReportModel;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\SearchLocationModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DirectFoundReportModel>
 */
final class DirectFoundReportModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'missing_form_code_unique' => fn (): string => MissingFormModel::factory()->create()->missing_form_code_unique,
            'tracking_cycle' => 1,
            'finder_type' => FinderType::External,
            'finder_user_code_unique' => null,
            'finder_name' => fake()->name(),
            'finder_organization' => fake()->company(),
            'finder_contact' => fake()->email(),
            'found_location_code_unique' => fn (): string => SearchLocationModel::factory()->create()->search_location_code_unique,
            'found_on' => now()->toDateString(),
            'result_summary' => fake()->sentence(),
            'recorded_by_user_code_unique' => fn (): string => UserModel::factory()->create()->user_code_unique,
            'recorded_by_name' => fake()->name(),
        ];
    }
}
