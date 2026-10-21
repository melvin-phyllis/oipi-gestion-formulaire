<?php

namespace App\Models\Registry;

use App\Traits\HasUniqueCode;
use App\Traits\HasUuid;
use Database\Factories\Registry\SearchLocationModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class SearchLocationModel extends Model
{
    /** @use HasFactory<SearchLocationModelFactory> */
    use HasFactory, HasUniqueCode, HasUuid, SoftDeletes;

    protected $table = 'reg_search_location_models';

    protected $fillable = [
        'location_code',
        'location_name',
        'description',
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
        return 'search_location_code_unique';
    }

    protected function uniqueCodePrefix(): string
    {
        return 'LOC';
    }
}
