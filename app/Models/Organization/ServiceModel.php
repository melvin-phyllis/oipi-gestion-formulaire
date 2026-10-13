<?php

namespace App\Models\Organization;

use App\Models\Administration\UserModel;
use App\Traits\HasUniqueCode;
use App\Traits\HasUuid;
use Database\Factories\Organization\ServiceModelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'directorate_code_unique',
    'service_code',
    'abbreviation',
    'service_name',
    'is_active',
    'display_order',
])]
final class ServiceModel extends Model
{
    /** @use HasFactory<ServiceModelFactory> */
    use HasFactory, HasUniqueCode, HasUuid, SoftDeletes;

    protected $table = 'org_service_models';

    public function directorate(): BelongsTo
    {
        return $this->belongsTo(
            DirectorateModel::class,
            'directorate_code_unique',
            'directorate_code_unique',
        );
    }

    public function users(): HasMany
    {
        return $this->hasMany(
            UserModel::class,
            'service_code_unique',
            'service_code_unique',
        );
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    protected function uniqueCodeColumn(): string
    {
        return 'service_code_unique';
    }

    protected function uniqueCodePrefix(): string
    {
        return 'SVC';
    }
}
