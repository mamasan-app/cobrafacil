<?php

namespace Database\Factories;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Payment;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition()
    {
        return [
            'type' => TransactionTypeEnum::Subscription->value,
            'status' => TransactionStatusEnum::Succeeded,
            'date' => now(),
            'amount_cents' => $this->faker->numberBetween(1000, 15000),
            'metadata' => ['reference' => $this->faker->uuid],
            'payment_id' => Payment::factory(),
            'stripe_payment_id' => null,
            'stripe_invoice_id' => null,
            'is_bs' => true,
        ];
    }

    public function fromStoreToUser(Store $store, User $user): static
    {
        return $this->state(function (array $attributes) use ($store, $user) {
            return [
                'from_type' => Store::class,
                'from_id' => $store->id,
                'to_type' => User::class,
                'to_id' => $user->id,
            ];
        });
    }

    public function fromUserToStore(User $user, Store $store): static
    {
        return $this->state(function (array $attributes) use ($user, $store) {
            return [
                'from_type' => User::class,
                'from_id' => $user->id,
                'to_type' => Store::class,
                'to_id' => $store->id,
            ];
        });
    }

    public function configure(): static
    {
        return $this->state(function (array $attributes) {
            if (! isset($attributes['from_type'])) {
                $fromType = $this->faker->randomElement([User::class, Store::class]);
                $toType = $fromType === User::class ? Store::class : User::class;

                $from = $fromType::factory()->create();
                $to = $toType::factory()->create();

                return [
                    'from_type' => $fromType,
                    'from_id' => $from->id,
                    'to_type' => $toType,
                    'to_id' => $to->id,
                ];
            }

            return [];
        });
    }
}
