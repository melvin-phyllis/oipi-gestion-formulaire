<?php

namespace App\Models\Audit;

use App\Models\Administration\UserModel;
use App\Traits\HasUniqueCode;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

final class AuditLogModel extends Model
{
    use HasUniqueCode, HasUuid;

    public const UPDATED_AT = null;

    protected $table = 'aud_audit_log_models';

    protected $fillable = [
        'category',
        'action_code',
        'actor_user_code_unique',
        'actor_name',
        'subject_type',
        'subject_uuid',
        'subject_code_unique',
        'subject_label',
        'description',
        'route_name',
        'http_method',
        'request_path',
        'ip_address',
        'user_agent',
        'metadata',
        'occurred_at',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'actor_user_code_unique', 'user_code_unique')
            ->withTrashed();
    }

    protected static function booted(): void
    {
        self::updating(fn () => throw new LogicException('Une entrée du journal d’audit est immuable.'));
        self::deleting(fn () => throw new LogicException('Une entrée du journal d’audit ne peut pas être supprimée.'));
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }

    protected function uniqueCodeColumn(): string
    {
        return 'audit_log_code_unique';
    }

    protected function uniqueCodePrefix(): string
    {
        return 'AUD';
    }
}
