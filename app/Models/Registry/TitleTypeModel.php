<?php

namespace App\Models\Registry;

use App\Traits\HasUniqueCode;
use App\Traits\HasUuid;
use Database\Factories\Registry\TitleTypeModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class TitleTypeModel extends Model
{
    /** @use HasFactory<TitleTypeModelFactory> */
    use HasFactory, HasUniqueCode, HasUuid, SoftDeletes;

    protected $table = 'reg_title_type_models';

    protected $fillable = [
        'title_type_code',
        'title_type_name',
        'is_active',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    protected function uniqueCodeColumn(): string
    {
        return 'title_type_code_unique';
    }

    protected function uniqueCodePrefix(): string
    {
        return 'TTY';
    }
}
