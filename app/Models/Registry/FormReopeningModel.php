<?php

namespace App\Models\Registry;

use App\Enums\Registry\FormStatus;
use App\Models\Administration\UserModel;
use App\Traits\HasUniqueCode;
use App\Traits\HasUuid;
use Database\Factories\Registry\FormReopeningModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class FormReopeningModel extends Model
{
    /** @use HasFactory<FormReopeningModelFactory> */
    use HasFactory, HasUniqueCode, HasUuid, SoftDeletes;

    protected $table = 'reg_form_reopening_models';

    protected $fillable = [
        'missing_form_code_unique',
        'previous_tracking_cycle',
        'new_tracking_cycle',
        'previous_status',
        'reason',
        'reopened_by_user_code_unique',
        'reopened_by_name',
        'reopened_at',
    ];

    public function missingForm(): BelongsTo
    {
        return $this->belongsTo(MissingFormModel::class, 'missing_form_code_unique', 'missing_form_code_unique');
    }

    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'reopened_by_user_code_unique', 'user_code_unique');
    }

    protected function casts(): array
    {
        return [
            'previous_tracking_cycle' => 'integer',
            'new_tracking_cycle' => 'integer',
            'previous_status' => FormStatus::class,
            'reopened_at' => 'immutable_datetime',
        ];
    }

    protected function uniqueCodeColumn(): string
    {
        return 'form_reopening_code_unique';
    }

    protected function uniqueCodePrefix(): string
    {
        return 'REO';
    }
}
