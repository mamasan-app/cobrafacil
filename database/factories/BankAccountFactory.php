<?php

namespace Database\Factories;

use App\Enums\BankEnum;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankAccount>
 */
class BankAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bank_code' => BankEnum::all()->random()[0]->code(),
            'phone_number' => '+58412'.fake()->randomNumber(7),
            'identity_document' => 'V'.fake()->randomNumber(8),
            'default_account' => true,
            'store_id' => Store::factory(),
            'user_id' => User::factory(),
        ];
    }
}
