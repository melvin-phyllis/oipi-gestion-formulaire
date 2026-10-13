<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasUniqueCode
{
    public static function bootHasUniqueCode(): void
    {
        static::creating(function (Model $model): void {
            $column = $model->uniqueCodeColumn();

            if (blank($model->getAttribute($column))) {
                $model->setAttribute(
                    $column,
                    $model->uniqueCodePrefix().'-'.Str::ulid(),
                );
            }
        });
    }

    abstract protected function uniqueCodeColumn(): string;

    abstract protected function uniqueCodePrefix(): string;
}
