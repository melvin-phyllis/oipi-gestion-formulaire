<?php

namespace Database\Factories\Registry;

use App\Enums\Registry\FinalizationStep;
use App\Models\Registry\FormFinalizationModel;
use App\Models\Registry\MissingFormModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FormFinalizationModel> */
final class FormFinalizationModelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'missing_form_code_unique' => fn (): string => MissingFormModel::factory()->create()->missing_form_code_unique,
            'tracking_cycle' => 1,
            'step' => FinalizationStep::Digitized,
            'performed_by_user_code_unique' => null,
            'performed_by_name' => null,
            'performed_at' => now(),
        ];
    }
}
