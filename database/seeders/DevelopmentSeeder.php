<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\BankAccount;
use App\Models\Frequency;
use App\Models\Plan;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    protected array $frequencies;

    public function __construct()
    {
        $this->frequencies = [];
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'first_name' => 'admin',
            'last_name' => 'admin',
            'email' => 'admin@cobrafacil.app',
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        $owner = User::factory()->create([
            'first_name' => 'owner',
            'last_name' => 'owner',
            'email' => 'store@cobrafacil.app',
        ]);
        $owner->assignRole('owner_store');

        $customer = User::factory()->create([
            'first_name' => 'customer',
            'last_name' => 'customer',
            'email' => 'customer@cobrafacil.app',
        ]);
        $customer->assignRole('customer');

        $employee = User::factory()->create([
            'first_name' => 'employee',
            'last_name' => 'employee',
            'email' => 'employee@cobrafacil.app',
        ]);
        $employee->assignRole('employee');
        $employee->assignRole('customer');

        $store = Store::create([
            'name' => 'Testore',
            'slug' => 'testore',
            'owner_id' => $owner->id,
        ]);

        Address::factory()
            ->for($store)
            ->create([
                'branch' => 'Caracas',
                'location' => 'Caracas, frente al metro de Chacao',
            ]);

        BankAccount::factory()
            ->for($customer)
            ->create([
                'default_account' => true,
            ]);

        BankAccount::factory()
            ->for($store)
            ->for($owner)
            ->create([
                'default_account' => true,
            ]);

        $store->users()->attach($owner->id, ['role' => 'owner_store']);
        $store->users()->attach($employee->id, ['role' => 'employee']);
        $store->users()->attach($customer->id, ['role' => 'customer']);
        $store->users()->attach($employee->id, ['role' => 'customer']);

        $this->makeFrequencies();
        $this->makeStorePlans($store);
    }

    public function makeFrequencies()
    {
        $this->frequencies[] = Frequency::create(['name' => 'Diaria', 'days_count' => 1]);
        $this->frequencies[] = Frequency::create(['name' => 'Semanal', 'days_count' => 7]);
        $this->frequencies[] = Frequency::create(['name' => 'Mensual', 'days_count' => 30]);
        $this->frequencies[] = Frequency::create(['name' => 'Anual', 'days_count' => 365]);
    }

    public function makeStorePlans(Store $store)
    {
        Plan::create([
            'name' => 'Plan Critico',
            'description' => 'Acceso limitado a funciones básicas.',
            'price_cents' => 1000,
            'published' => true,
            'featured' => false,
            'store_id' => $store->id,
            'frequency_id' => $this->frequencies[0]->id,
            'free_days' => 1,
            'grace_period' => 3,
            'infinite_duration' => false,
            'duration' => 6,
        ]);

        Plan::create([
            'name' => 'Plan Básico',
            'description' => 'Acceso limitado a funciones básicas.',
            'price_cents' => 5000,
            'published' => true,
            'featured' => false,
            'store_id' => $store->id,
            'frequency_id' => $this->frequencies[1]->id,
            'free_days' => 7,
            'grace_period' => 3,
            'infinite_duration' => false,
            'duration' => 14,
        ]);

        Plan::create([
            'name' => 'Plan Pro',
            'description' => 'Acceso completo a todas las funciones.',
            'price_cents' => 15000,
            'published' => true,
            'featured' => true,
            'store_id' => $store->id,
            'frequency_id' => $this->frequencies[2]->id,
            'free_days' => 14,
            'grace_period' => 5,
            'infinite_duration' => true,
        ]);

        Plan::create([
            'name' => 'Plan Premium',
            'description' => 'Acceso completo con soporte prioritario.',
            'price_cents' => 100000,
            'published' => true,
            'featured' => true,
            'store_id' => $store->id,
            'frequency_id' => $this->frequencies[3]->id,
            'free_days' => 30,
            'grace_period' => 10,
            'infinite_duration' => true,
        ]);

        Plan::create([
            'name' => 'Prueba',
            'description' => 'Acceso completo con soporte prioritario.',
            'price_cents' => 10,
            'published' => true,
            'featured' => true,
            'store_id' => $store->id,
            'frequency_id' => $this->frequencies[3]->id,
            'free_days' => 30,
            'grace_period' => 10,
            'infinite_duration' => true,
        ]);
    }
}
