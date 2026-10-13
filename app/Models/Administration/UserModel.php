<?php

namespace App\Models\Administration;

use App\Models\Organization\DirectorateModel;
use App\Models\Organization\ServiceModel;
use App\Notifications\Authentication\ResetPasswordNotification;
use App\Traits\HasUniqueCode;
use App\Traits\HasUuid;
use Database\Factories\Administration\UserModelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'role_code_unique',
    'service_code_unique',
    'username',
    'last_name',
    'first_names',
    'email',
    'phone',
    'profile_photo_path',
    'password',
    'is_active',
    'must_change_password',
    'session_version',
    'last_login_at',
    'created_by_code_unique',
])]
#[Hidden([
    'password',
    'remember_token',
])]
final class UserModel extends Authenticatable
{
    /** @use HasFactory<UserModelFactory> */
    use HasFactory, HasUniqueCode, HasUuid, Notifiable, SoftDeletes;

    protected $table = 'adm_user_models';

    public function role(): BelongsTo
    {
        return $this->belongsTo(
            RoleModel::class,
            'role_code_unique',
            'role_code_unique',
        );
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(
            ServiceModel::class,
            'service_code_unique',
            'service_code_unique',
        );
    }

    public function directorate(): HasOneThrough
    {
        return $this->hasOneThrough(
            DirectorateModel::class,
            ServiceModel::class,
            'service_code_unique',
            'directorate_code_unique',
            'service_code_unique',
            'directorate_code_unique',
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'created_by_code_unique',
            'user_code_unique',
        );
    }

    public function createdUsers(): HasMany
    {
        return $this->hasMany(
            self::class,
            'created_by_code_unique',
            'user_code_unique',
        );
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification((string) $token));
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'immutable_datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'session_version' => 'integer',
            'last_login_at' => 'immutable_datetime',
        ];
    }

    protected function uniqueCodeColumn(): string
    {
        return 'user_code_unique';
    }

    protected function uniqueCodePrefix(): string
    {
        return 'USR';
    }
}
