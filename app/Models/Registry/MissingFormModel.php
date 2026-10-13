<?php

namespace App\Models\Registry;

use App\Enums\Registry\FormPriority;
use App\Enums\Registry\FormStatus;
use App\Models\Administration\UserModel;
use App\Models\Mission\FoundReportReviewModel;
use App\Models\Mission\SearchHistoryModel;
use App\Models\Mission\SearchMissionModel;
use App\Traits\HasUniqueCode;
use App\Traits\HasUuid;
use Database\Factories\Registry\MissingFormModelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'registry_number',
    'title_type_code_unique',
    'form_year',
    'form_number',
    'status',
    'status_before_archive',
    'priority',
    'tracking_cycle',
    'last_search_location_code_unique',
    'last_responsible_user_code_unique',
    'search_due_date',
    'last_search_date',
    'resolved_at',
    'result_summary',
    'archive_reason',
    'archived_by_user_code_unique',
    'archived_at',
    'created_by_user_code_unique',
    'updated_by_user_code_unique',
    'source_import_code_unique',
])]
final class MissingFormModel extends Model
{
    /** @use HasFactory<MissingFormModelFactory> */
    use HasFactory, HasUniqueCode, HasUuid, SoftDeletes;

    protected $table = 'reg_missing_form_models';

    public function titleType(): BelongsTo
    {
        return $this->belongsTo(
            TitleTypeModel::class,
            'title_type_code_unique',
            'title_type_code_unique',
        );
    }

    public function lastSearchLocation(): BelongsTo
    {
        return $this->belongsTo(
            SearchLocationModel::class,
            'last_search_location_code_unique',
            'search_location_code_unique',
        );
    }

    public function lastResponsible(): BelongsTo
    {
        return $this->belongsTo(
            UserModel::class,
            'last_responsible_user_code_unique',
            'user_code_unique',
        );
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(
            UserModel::class,
            'archived_by_user_code_unique',
            'user_code_unique',
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            UserModel::class,
            'created_by_user_code_unique',
            'user_code_unique',
        );
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            UserModel::class,
            'updated_by_user_code_unique',
            'user_code_unique',
        );
    }

    public function missions(): HasMany
    {
        return $this->hasMany(SearchMissionModel::class, 'missing_form_code_unique', 'missing_form_code_unique');
    }

    public function searchHistory(): HasMany
    {
        return $this->hasMany(SearchHistoryModel::class, 'missing_form_code_unique', 'missing_form_code_unique');
    }

    public function directFoundReport(): HasOne
    {
        return $this->hasOne(
            DirectFoundReportModel::class,
            'missing_form_code_unique',
            'missing_form_code_unique',
        );
    }

    public function foundReviews(): HasMany
    {
        return $this->hasMany(FoundReportReviewModel::class, 'missing_form_code_unique', 'missing_form_code_unique');
    }

    public function finalizations(): HasMany
    {
        return $this->hasMany(FormFinalizationModel::class, 'missing_form_code_unique', 'missing_form_code_unique');
    }

    public function reopenings(): HasMany
    {
        return $this->hasMany(FormReopeningModel::class, 'missing_form_code_unique', 'missing_form_code_unique');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(FormAttachmentModel::class, 'missing_form_code_unique', 'missing_form_code_unique');
    }

    public function sourceImport(): BelongsTo
    {
        return $this->belongsTo(
            MissingFormImportModel::class,
            'source_import_code_unique',
            'missing_form_import_code_unique',
        );
    }

    protected function casts(): array
    {
        return [
            'form_year' => 'integer',
            'status' => FormStatus::class,
            'status_before_archive' => FormStatus::class,
            'priority' => FormPriority::class,
            'tracking_cycle' => 'integer',
            'search_due_date' => 'immutable_date',
            'last_search_date' => 'immutable_date',
            'resolved_at' => 'immutable_datetime',
            'archived_at' => 'immutable_datetime',
        ];
    }

    protected function uniqueCodeColumn(): string
    {
        return 'missing_form_code_unique';
    }

    protected function uniqueCodePrefix(): string
    {
        return 'FRM';
    }
}
