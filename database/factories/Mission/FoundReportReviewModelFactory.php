<?php

namespace Database\Factories\Mission;

use App\Enums\Mission\FoundReportDecision;
use App\Models\Administration\UserModel;
use App\Models\Mission\FoundReportReviewModel;
use App\Models\Mission\SearchMissionModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FoundReportReviewModel> */
final class FoundReportReviewModelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'search_mission_code_unique' => fn (): string => SearchMissionModel::factory()->create()->search_mission_code_unique,
            'missing_form_code_unique' => fn (array $attributes): string => SearchMissionModel::query()
                ->where('search_mission_code_unique', $attributes['search_mission_code_unique'])
                ->valueOrFail('missing_form_code_unique'),
            'tracking_cycle' => 1,
            'decision' => FoundReportDecision::Confirmed,
            'rejection_reason' => null,
            'reviewed_by_user_code_unique' => fn (): string => UserModel::factory()->create()->user_code_unique,
            'reviewed_at' => now(),
        ];
    }
}
