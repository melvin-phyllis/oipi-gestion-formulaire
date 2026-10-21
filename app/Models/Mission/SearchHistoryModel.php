<?php

namespace App\Models\Mission;

use App\Enums\Mission\MissionResult;
use App\Enums\Registry\FormStatus;
use App\Models\Administration\UserModel;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\SearchLocationModel;
use App\Traits\HasUniqueCode;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SearchHistoryModel extends Model
{
    use HasUniqueCode, HasUuid;

    protected $table = 'mis_search_history_models';

    protected $fillable = [
        'search_mission_code_unique',
        'missing_form_code_unique',
        'tracking_cycle',
        'search_location_code_unique',
        'search_location_name',
        'responsible_user_code_unique',
        'responsible_name',
        'form_status',
        'mission_result',
        'searched_on',
        'result_summary',
        'recorded_by_user_code_unique',
        'recorded_by_name',
        'source',
    ];

    public function mission(): BelongsTo
    {
        return $this->belongsTo(SearchMissionModel::class, 'search_mission_code_unique', 'search_mission_code_unique');
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

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'recorded_by_user_code_unique', 'user_code_unique');
    }

    protected function casts(): array
    {
        return [
            'tracking_cycle' => 'integer',
            'form_status' => FormStatus::class,
            'mission_result' => MissionResult::class,
            'searched_on' => 'immutable_date',
        ];
    }

    protected function uniqueCodeColumn(): string
    {
        return 'search_history_code_unique';
    }

    protected function uniqueCodePrefix(): string
    {
        return 'HIS';
    }
}
