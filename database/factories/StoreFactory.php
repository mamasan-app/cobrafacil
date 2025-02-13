<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Store>
 */
class StoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'url' => fake()->url(),
            'slug' => fake()->slug(),
            'constitutive_document_path' => null,
            'rif_path' => null,
            'description' => fake()->text(),
            'verified' => fake()->boolean(),
            'logo' => null,
            'owner_id' => User::factory(),
        ];
    }
}
