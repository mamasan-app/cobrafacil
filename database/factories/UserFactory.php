<?php

namespace Database\Factories;

use App\Enums\IdentityPrefixEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->bothify('########'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone_number' => '+58424'.fake()->bothify('#######'),
            'identity_prefix' => fake()->randomElement(IdentityPrefixEnum::all()->toArray()),
            'identity_number' => fake()->numberBetween(10_000_000, 40_000_000),
            'password' => static::$password ??= Hash::make('password'),
            'birth_date' => $this->faker->date(),
            'address' => fake()->address(),
            'selfie_path' => null,
            'ci_picture_path' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
