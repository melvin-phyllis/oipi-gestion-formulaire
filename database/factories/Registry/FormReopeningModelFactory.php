<?php

namespace Database\Factories\Registry;

use App\Enums\Registry\FormStatus;
use App\Models\Registry\FormReopeningModel;
use App\Models\Registry\MissingFormModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FormReopeningModel> */
final class FormReopeningModelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'missing_form_code_unique' => fn (): string => MissingFormModel::factory()->create()->missing_form_code_unique,
            'previous_tracking_cycle' => 1,
            'new_tracking_cycle' => 2,
            'previous_status' => FormStatus::Found,
            'reason' => fake()->sentence(),
            'reopened_by_user_code_unique' => null,
            'reopened_by_name' => null,
            'reopened_at' => now(),
        ];
    }
}
