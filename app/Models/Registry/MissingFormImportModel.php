<?php

namespace App\Models\Registry;

use App\Enums\Registry\RegistryImportStatus;
use App\Models\Administration\UserModel;
use App\Traits\HasUniqueCode;
use App\Traits\HasUuid;
use Database\Factories\Registry\MissingFormImportModelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'status',
    'disk',
    'file_path',
    'original_file_name',
    'size_bytes',
    'total_rows',
    'processed_rows',
    'imported_rows',
    'error_count',
    'errors',
    'created_by_user_code_unique',
    'started_at',
    'completed_at',
    'failed_at',
])]
final class MissingFormImportModel extends Model
{
    /** @use HasFactory<MissingFormImportModelFactory> */
    use HasFactory, HasUniqueCode, HasUuid, SoftDeletes;

    protected $table = 'reg_missing_form_import_models';

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            UserModel::class,
            'created_by_user_code_unique',
            'user_code_unique',
        )->withTrashed();
    }

    public function importedForms(): HasMany
    {
        return $this->hasMany(
            MissingFormModel::class,
            'source_import_code_unique',
            'missing_form_import_code_unique',
        );
    }

    protected function casts(): array
    {
        return [
            'status' => RegistryImportStatus::class,
            'size_bytes' => 'integer',
            'total_rows' => 'integer',
            'processed_rows' => 'integer',
            'imported_rows' => 'integer',
            'error_count' => 'integer',
            'errors' => 'array',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'failed_at' => 'immutable_datetime',
        ];
    }

    protected function uniqueCodeColumn(): string
    {
        return 'missing_form_import_code_unique';
    }

    protected function uniqueCodePrefix(): string
    {
        return 'IMP';
    }
}
