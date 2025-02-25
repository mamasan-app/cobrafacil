<?php

namespace Database\Seeders;

use App\Models\Frequency;
use Illuminate\Database\Seeder;

class FrequencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Frequency::firstOrCreate(['name' => 'Diaria', 'days_count' => 1]);
        Frequency::firstOrCreate(['name' => 'Semanal', 'days_count' => 7]);
        Frequency::firstOrCreate(['name' => 'Quincenal', 'days_count' => 15]);
        Frequency::firstOrCreate(['name' => 'Mensual', 'days_count' => 30]);
        Frequency::firstOrCreate(['name' => 'Anual', 'days_count' => 365]);
    }
}
