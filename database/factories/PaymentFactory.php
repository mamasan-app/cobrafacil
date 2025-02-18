<?php

namespace Database\Factories;

use App\Enums\PaymentStatusEnum;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition()
    {
        return [
            'stripe_invoice_id' => null,
            'status' => PaymentStatusEnum::Pending,
            'amount_cents' => $this->faker->numberBetween(500, 15000),
            'due_date' => $this->faker->dateTimeBetween('-1 week', '+1 week'),
            'paid_date' => null,
            'is_bs' => $this->faker->boolean,
            'paid' => fake()->boolean(),
        ];
    }
}
