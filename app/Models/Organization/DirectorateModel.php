<?php

namespace App\Models\Organization;

use App\Traits\HasUniqueCode;
use App\Traits\HasUuid;
use Database\Factories\Organization\DirectorateModelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'directorate_code',
    'directorate_name',
    'is_active',
    'display_order',
])]
final class DirectorateModel extends Model
{
    /** @use HasFactory<DirectorateModelFactory> */
    use HasFactory, HasUniqueCode, HasUuid, SoftDeletes;

    protected $table = 'org_directorate_models';

    public function services(): HasMany
    {
        return $this->hasMany(
            ServiceModel::class,
            'directorate_code_unique',
            'directorate_code_unique',
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
        return 'directorate_code_unique';
    }

    protected function uniqueCodePrefix(): string
    {
        return 'DIR';
    }
}
