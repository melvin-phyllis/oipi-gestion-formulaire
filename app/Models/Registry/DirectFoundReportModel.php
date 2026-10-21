<?php

namespace App\Models\Registry;

use App\Enums\Registry\FinderType;
use App\Models\Administration\UserModel;
use App\Traits\HasUniqueCode;
use App\Traits\HasUuid;
use Database\Factories\Registry\DirectFoundReportModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

final class DirectFoundReportModel extends Model
{
    /** @use HasFactory<DirectFoundReportModelFactory> */
    use HasFactory, HasUniqueCode, HasUuid;

    protected $table = 'reg_direct_found_report_models';

    protected $fillable = [
        'missing_form_code_unique',
        'tracking_cycle',
        'finder_type',
        'finder_user_code_unique',
        'finder_name',
        'finder_organization',
        'finder_contact',
        'found_location_code_unique',
        'found_on',
        'result_summary',
        'recorded_by_user_code_unique',
        'recorded_by_name',
    ];

    public function missingForm(): BelongsTo
    {
        return $this->belongsTo(MissingFormModel::class, 'missing_form_code_unique', 'missing_form_code_unique');
    }

    public function finder(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'finder_user_code_unique', 'user_code_unique')->withTrashed();
    }

    public function foundLocation(): BelongsTo
    {
        return $this->belongsTo(SearchLocationModel::class, 'found_location_code_unique', 'search_location_code_unique');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'recorded_by_user_code_unique', 'user_code_unique')->withTrashed();
    }

    protected static function booted(): void
    {
        self::updating(fn () => throw new LogicException('Une déclaration de découverte est immuable.'));
        self::deleting(fn () => throw new LogicException('Une déclaration de découverte ne peut pas être supprimée.'));
    }

    protected function casts(): array
    {
        return [
            'tracking_cycle' => 'integer',
            'finder_type' => FinderType::class,
            'found_on' => 'immutable_date',
        ];
    }

    protected function uniqueCodeColumn(): string
    {
        return 'direct_found_report_code_unique';
    }

    protected function uniqueCodePrefix(): string
    {
        return 'DFR';
    }
}
