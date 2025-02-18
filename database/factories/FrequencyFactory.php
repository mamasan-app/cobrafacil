<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Frequency>
 */
class FrequencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $frequency = ['Diario' => 1, 'Semanal' => 7, 'Anual' => 365, 'Mensual' => 30];
        $frequencyItem = array_rand($frequency);
        $daysCount = $frequency[$frequencyItem];

        return [
            'name' => $frequencyItem,
            'days_count' => $daysCount,
            'active' => true,
        ];
    }
}
