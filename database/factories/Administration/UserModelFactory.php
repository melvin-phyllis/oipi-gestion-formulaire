<?php

namespace Database\Factories\Administration;

use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use App\Models\Organization\ServiceModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<UserModel>
 */
class UserModelFactory extends Factory
{
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_code_unique' => fn (): string => RoleModel::factory()
                ->create()
                ->role_code_unique,
            'service_code_unique' => fn (): string => ServiceModel::factory()
                ->create()
                ->service_code_unique,
            'username' => fake()->unique()->userName(),
            'last_name' => fake()->lastName(),
            'first_names' => fake()->firstName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'profile_photo_path' => null,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'is_active' => true,
            'must_change_password' => false,
            'session_version' => 1,
            'last_login_at' => null,
            'created_by_code_unique' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function mustChangePassword(): static
    {
        return $this->state(fn (array $attributes): array => [
            'must_change_password' => true,
        ]);
    }
}
