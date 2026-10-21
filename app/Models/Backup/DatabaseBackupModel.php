<?php

namespace App\Models\Backup;

use App\Enums\Backup\BackupType;
use App\Models\Administration\UserModel;
use App\Traits\HasUniqueCode;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class DatabaseBackupModel extends Model
{
    use HasUniqueCode, HasUuid, SoftDeletes;

    protected $table = 'bkp_backup_models';

    protected $fillable = [
        'backup_type',
        'disk',
        'file_path',
        'file_name',
        'size_bytes',
        'sha256',
        'database_name',
        'created_by_user_code_unique',
        'creator_name',
        'deleted_by_user_code_unique',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'created_by_user_code_unique', 'user_code_unique')
            ->withTrashed();
    }

    protected function casts(): array
    {
        return [
            'backup_type' => BackupType::class,
            'size_bytes' => 'integer',
        ];
    }

    protected function uniqueCodeColumn(): string
    {
        return 'database_backup_code_unique';
    }

    protected function uniqueCodePrefix(): string
    {
        return 'BKP';
    }
}
