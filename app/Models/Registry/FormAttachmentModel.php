<?php

namespace App\Models\Registry;

use App\Models\Administration\UserModel;
use App\Traits\HasUniqueCode;
use App\Traits\HasUuid;
use Database\Factories\Registry\FormAttachmentModelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'uuid',
    'missing_form_code_unique',
    'disk',
    'file_path',
    'stored_name',
    'original_name',
    'download_name',
    'mime_type',
    'extension',
    'size_bytes',
    'sha256',
    'uploaded_by_user_code_unique',
    'uploader_name',
    'deleted_by_user_code_unique',
])]
final class FormAttachmentModel extends Model
{
    /** @use HasFactory<FormAttachmentModelFactory> */
    use HasFactory, HasUniqueCode, HasUuid, SoftDeletes;

    protected $table = 'reg_form_attachment_models';

    public function missingForm(): BelongsTo
    {
        return $this->belongsTo(
            MissingFormModel::class,
            'missing_form_code_unique',
            'missing_form_code_unique',
        );
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(
            UserModel::class,
            'uploaded_by_user_code_unique',
            'user_code_unique',
        )->withTrashed();
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(
            UserModel::class,
            'deleted_by_user_code_unique',
            'user_code_unique',
        )->withTrashed();
    }

    protected function casts(): array
    {
        return ['size_bytes' => 'integer'];
    }

    protected function uniqueCodeColumn(): string
    {
        return 'form_attachment_code_unique';
    }

    protected function uniqueCodePrefix(): string
    {
        return 'ATT';
    }
}
