<?php

namespace Database\Factories;

use App\Models\Frequency;
use App\Models\Plan;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'description' => $this->faker->sentence,
            'price_cents' => $this->faker->numberBetween(1000, 100000),
            'published' => $this->faker->boolean,
            'featured' => $this->faker->boolean,
            'frequency_id' => Frequency::factory(),
            'store_id' => Store::factory(),
            'free_days' => $this->faker->numberBetween(0, 10),
            'grace_period' => $this->faker->numberBetween(0, 10),
            'infinite_duration' => $this->faker->boolean,
            'duration' => $this->faker->numberBetween(1, 30),
            'stripe_product_id' => null,
        ];
    }
}
