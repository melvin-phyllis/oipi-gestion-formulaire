<?php

namespace Database\Factories\Registry;

use App\Models\Registry\TitleTypeModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<TitleTypeModel> */
final class TitleTypeModelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title_type_code' => Str::upper(Str::random(8)),
            'title_type_name' => fake()->unique()->words(3, true),
            'is_active' => true,
            'display_order' => fake()->numberBetween(1, 999),
        ];
    }
}
