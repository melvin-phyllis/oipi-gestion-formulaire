<?php

namespace Database\Factories\Mission;

use App\Enums\Mission\MissionStatus;
use App\Enums\Registry\FormPriority;
use App\Models\Administration\UserModel;
use App\Models\Mission\SearchMissionModel;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\SearchLocationModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SearchMissionModel> */
final class SearchMissionModelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'parent_mission_code_unique' => null,
            'missing_form_code_unique' => fn (): string => MissingFormModel::factory()->create()->missing_form_code_unique,
            'tracking_cycle' => 1,
            'search_location_code_unique' => fn (): string => SearchLocationModel::factory()->create()->search_location_code_unique,
            'responsible_user_code_unique' => fn (): string => UserModel::factory()->create()->user_code_unique,
            'assigned_by_user_code_unique' => null,
            'closed_by_user_code_unique' => null,
            'status' => MissionStatus::Assigned,
            'result' => null,
            'result_summary' => null,
            'priority' => FormPriority::Normal,
            'assigned_at' => now(),
            'due_date' => now()->addWeek()->toDateString(),
            'searched_on' => null,
            'closed_at' => null,
            'cancellation_reason' => null,
            'active_assignment_key' => null,
        ];
    }
}
