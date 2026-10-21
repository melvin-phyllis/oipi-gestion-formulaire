<?php

namespace App\Models\Mission;

use App\Enums\Mission\MissionResult;
use App\Enums\Mission\MissionStatus;
use App\Enums\Registry\FormPriority;
use App\Models\Administration\UserModel;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\SearchLocationModel;
use App\Traits\HasUniqueCode;
use App\Traits\HasUuid;
use Database\Factories\Mission\SearchMissionModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

final class SearchMissionModel extends Model
{
    /** @use HasFactory<SearchMissionModelFactory> */
    use HasFactory, HasUniqueCode, HasUuid, SoftDeletes;

    protected $table = 'mis_search_mission_models';

    protected $fillable = [
        'parent_mission_code_unique',
        'missing_form_code_unique',
        'tracking_cycle',
        'search_location_code_unique',
        'responsible_user_code_unique',
        'assigned_by_user_code_unique',
        'closed_by_user_code_unique',
        'status',
        'result',
        'result_summary',
        'priority',
        'assigned_at',
        'due_date',
        'searched_on',
        'closed_at',
        'cancellation_reason',
        'active_assignment_key',
    ];

    public function parentMission(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_mission_code_unique', 'search_mission_code_unique');
    }

    public function missingForm(): BelongsTo
    {
        return $this->belongsTo(MissingFormModel::class, 'missing_form_code_unique', 'missing_form_code_unique');
    }

    public function searchLocation(): BelongsTo
    {
        return $this->belongsTo(SearchLocationModel::class, 'search_location_code_unique', 'search_location_code_unique');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'responsible_user_code_unique', 'user_code_unique');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'assigned_by_user_code_unique', 'user_code_unique');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'closed_by_user_code_unique', 'user_code_unique');
    }

    public function history(): HasOne
    {
        return $this->hasOne(SearchHistoryModel::class, 'search_mission_code_unique', 'search_mission_code_unique');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(MissionReminderModel::class, 'search_mission_code_unique', 'search_mission_code_unique');
    }

    public function foundReview(): HasOne
    {
        return $this->hasOne(FoundReportReviewModel::class, 'search_mission_code_unique', 'search_mission_code_unique');
    }

    protected function casts(): array
    {
        return [
            'tracking_cycle' => 'integer',
            'status' => MissionStatus::class,
            'result' => MissionResult::class,
            'priority' => FormPriority::class,
            'assigned_at' => 'immutable_datetime',
            'due_date' => 'immutable_date',
            'searched_on' => 'immutable_date',
            'closed_at' => 'immutable_datetime',
        ];
    }

    protected function uniqueCodeColumn(): string
    {
        return 'search_mission_code_unique';
    }

    protected function uniqueCodePrefix(): string
    {
        return 'MSN';
    }
}
