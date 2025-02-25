<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $seeders = [
            RoleSeeder::class,
            RolePermissionSeeder::class,
            FrequencySeeder::class,
        ];

        if (config('app.env' === 'local')) {
            $seeders[] = DevelopmentSeeder::class;
        }

        $this->call($seeders);
    }
}
