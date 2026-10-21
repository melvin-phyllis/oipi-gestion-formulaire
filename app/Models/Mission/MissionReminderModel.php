<?php

namespace App\Models\Mission;

use App\Enums\Mission\MissionReminderType;
use App\Models\Administration\UserModel;
use App\Traits\HasUniqueCode;
use App\Traits\HasUuid;
use Database\Factories\Mission\MissionReminderModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class MissionReminderModel extends Model
{
    /** @use HasFactory<MissionReminderModelFactory> */
    use HasFactory, HasUniqueCode, HasUuid, SoftDeletes;

    protected $table = 'mis_mission_reminder_models';

    protected $fillable = [
        'search_mission_code_unique',
        'recipient_user_code_unique',
        'type',
        'reminder_date',
        'days_delta',
        'notification_id',
        'database_sent_at',
        'email_attempted_at',
        'email_sent_at',
        'email_error',
    ];

    public function mission(): BelongsTo
    {
        return $this->belongsTo(SearchMissionModel::class, 'search_mission_code_unique', 'search_mission_code_unique');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'recipient_user_code_unique', 'user_code_unique');
    }

    protected function casts(): array
    {
        return [
            'type' => MissionReminderType::class,
            'reminder_date' => 'immutable_date',
            'days_delta' => 'integer',
            'database_sent_at' => 'immutable_datetime',
            'email_attempted_at' => 'immutable_datetime',
            'email_sent_at' => 'immutable_datetime',
        ];
    }

    protected function uniqueCodeColumn(): string
    {
        return 'mission_reminder_code_unique';
    }

    protected function uniqueCodePrefix(): string
    {
        return 'RMD';
    }
}
