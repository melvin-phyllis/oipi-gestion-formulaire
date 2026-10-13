<?php

namespace App\Models\Mission;

use App\Enums\Mission\FoundReportDecision;
use App\Models\Administration\UserModel;
use App\Models\Registry\MissingFormModel;
use App\Traits\HasUniqueCode;
use App\Traits\HasUuid;
use Database\Factories\Mission\FoundReportReviewModelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'search_mission_code_unique',
    'missing_form_code_unique',
    'tracking_cycle',
    'decision',
    'rejection_reason',
    'reviewed_by_user_code_unique',
    'reviewed_at',
])]
final class FoundReportReviewModel extends Model
{
    /** @use HasFactory<FoundReportReviewModelFactory> */
    use HasFactory, HasUniqueCode, HasUuid, SoftDeletes;

    protected $table = 'mis_found_report_review_models';

    public function mission(): BelongsTo
    {
        return $this->belongsTo(SearchMissionModel::class, 'search_mission_code_unique', 'search_mission_code_unique');
    }

    public function missingForm(): BelongsTo
    {
        return $this->belongsTo(MissingFormModel::class, 'missing_form_code_unique', 'missing_form_code_unique');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'reviewed_by_user_code_unique', 'user_code_unique');
    }

    protected function casts(): array
    {
        return [
            'tracking_cycle' => 'integer',
            'decision' => FoundReportDecision::class,
            'reviewed_at' => 'immutable_datetime',
        ];
    }

    protected function uniqueCodeColumn(): string
    {
        return 'found_report_review_code_unique';
    }

    protected function uniqueCodePrefix(): string
    {
        return 'FRV';
    }
}
