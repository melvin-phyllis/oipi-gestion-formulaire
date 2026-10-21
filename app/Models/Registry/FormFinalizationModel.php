<?php

namespace App\Models\Registry;

use App\Enums\Registry\FinalizationStep;
use App\Models\Administration\UserModel;
use App\Traits\HasUniqueCode;
use App\Traits\HasUuid;
use Database\Factories\Registry\FormFinalizationModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class FormFinalizationModel extends Model
{
    /** @use HasFactory<FormFinalizationModelFactory> */
    use HasFactory, HasUniqueCode, HasUuid, SoftDeletes;

    protected $table = 'reg_form_finalization_models';

    protected $fillable = [
        'missing_form_code_unique',
        'tracking_cycle',
        'step',
        'performed_by_user_code_unique',
        'performed_by_name',
        'performed_at',
    ];

    public function missingForm(): BelongsTo
    {
        return $this->belongsTo(MissingFormModel::class, 'missing_form_code_unique', 'missing_form_code_unique');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'performed_by_user_code_unique', 'user_code_unique');
    }

    protected function casts(): array
    {
        return [
            'tracking_cycle' => 'integer',
            'step' => FinalizationStep::class,
            'performed_at' => 'immutable_datetime',
        ];
    }

    protected function uniqueCodeColumn(): string
    {
        return 'form_finalization_code_unique';
    }

    protected function uniqueCodePrefix(): string
    {
        return 'FIN';
    }
}
