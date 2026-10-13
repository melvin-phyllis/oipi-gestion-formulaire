<?php

namespace Database\Factories\Registry;

use App\Enums\Registry\FormPriority;
use App\Enums\Registry\FormStatus;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\TitleTypeModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<MissingFormModel> */
final class MissingFormModelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'registry_number' => 'TEST-'.Str::upper(Str::random(12)),
            'title_type_code_unique' => fn (): string => TitleTypeModel::factory()
                ->create()
                ->title_type_code_unique,
            'form_year' => fake()->numberBetween(2000, (int) now()->year),
            'form_number' => Str::upper(fake()->unique()->bothify('?-####-######')),
            'status' => FormStatus::Missing,
            'status_before_archive' => null,
            'priority' => FormPriority::Normal,
            'tracking_cycle' => 1,
            'last_search_location_code_unique' => null,
            'last_responsible_user_code_unique' => null,
            'search_due_date' => null,
            'last_search_date' => null,
            'resolved_at' => null,
            'result_summary' => null,
            'archive_reason' => null,
            'archived_by_user_code_unique' => null,
            'archived_at' => null,
            'created_by_user_code_unique' => null,
            'updated_by_user_code_unique' => null,
        ];
    }
}
