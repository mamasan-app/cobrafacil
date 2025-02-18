<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatusEnum;
use App\Models\Plan;
use App\Models\Store;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition()
    {
        $now = Carbon::now();

        $plan = Plan::factory()->create();

        return [
            'user_id' => User::factory(),
            'store_id' => Store::factory(),
            'service_id' => $plan->id,
            'service_name' => $plan->name,
            'service_description' => $plan->description,
            'service_price_cents' => $plan->price_cents,
            'status' => fake()->randomElement(SubscriptionStatusEnum::cases()),
            'trial_ends_at' => $now->copy()->addDays(30),
            'renews_at' => $now->copy()->addDays(365),
            'ends_at' => null,
            'last_notification_at' => null,
            'expires_at' => $now->copy()->addDays(375),
            'frequency_days' => 30,
        ];
    }
}
