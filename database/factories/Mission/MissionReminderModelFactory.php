<?php

namespace Database\Factories\Mission;

use App\Enums\Mission\MissionReminderType;
use App\Models\Administration\UserModel;
use App\Models\Mission\MissionReminderModel;
use App\Models\Mission\SearchMissionModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MissionReminderModel> */
final class MissionReminderModelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'search_mission_code_unique' => fn (): string => SearchMissionModel::factory()->create()->search_mission_code_unique,
            'recipient_user_code_unique' => fn (): string => UserModel::factory()->create()->user_code_unique,
            'type' => MissionReminderType::BeforeDue,
            'reminder_date' => today(),
            'days_delta' => 2,
            'notification_id' => null,
            'database_sent_at' => null,
            'email_attempted_at' => null,
            'email_sent_at' => null,
            'email_error' => null,
        ];
    }
}
