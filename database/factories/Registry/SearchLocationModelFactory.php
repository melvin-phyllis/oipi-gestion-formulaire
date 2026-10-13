<?php

namespace Database\Factories\Registry;

use App\Models\Registry\SearchLocationModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<SearchLocationModel> */
final class SearchLocationModelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location_code' => 'TEST_'.Str::upper(Str::random(12)),
            'location_name' => fake()->unique()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
            'display_order' => fake()->numberBetween(1, 999),
        ];
    }
}
