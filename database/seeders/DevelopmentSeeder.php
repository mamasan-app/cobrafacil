<?php

namespace Database\Seeders;

use App\Enums\PaymentStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Address;
use App\Models\BankAccount;
use App\Models\Frequency;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Store;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DevelopmentSeeder extends Seeder
{
    protected Collection $frequencies;

    protected ?Subscription $subscription;

    public function __construct()
    {
        $this->frequencies = Frequency::all();
        $this->subscription = null;
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

        $this->makeStorePlans($store);
        $this->makeSubscriptions($store);
        $this->makeTransactions($store);
    }

    public function makeSubscriptions(Store $store): void
    {
        $customer = User::whereEmail('customer@cobrafacil.app')->first();
        $plan = Plan::where('name', 'Prueba')->first();

        $this->subscription = Subscription::factory()->create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'service_id' => $plan->id,
            'service_name' => $plan->name,
            'service_description' => $plan->description,
            'service_price_cents' => $plan->price_cents,
            'status' => 'active',
            'trial_ends_at' => now()->addDays(30),
            'renews_at' => now()->addDays(365),
            'expires_at' => now()->addDays(375),
            'frequency_days' => $plan->frequency->days_count,
        ]);
    }

    public function makeStorePlans(Store $store): void
    {
        Plan::factory()->create([
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

        Plan::factory()->create([
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

        Plan::factory()->create([
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

        Plan::factory()->create([
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

        Plan::factory()->create([
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

    public function makeTransactions(Store $store): void
    {
        $customer = User::whereEmail('customer@cobrafacil.app')->first();

        $approvedPayment = Payment::factory()->create([
            'subscription_id' => $this->subscription->id,
            'status' => PaymentStatusEnum::Completed,
            'amount_cents' => $this->subscription->service_price_cents,
            'due_date' => now()->subDays(10),
            'paid_date' => now()->subDays(5),
            'is_bs' => true,
            'paid' => true,
        ]);

        Transaction::factory()
            ->fromUserToStore($customer, $store)
            ->create([
                'type' => TransactionTypeEnum::Subscription->value,
                'status' => TransactionStatusEnum::Succeeded,
                'date' => now()->subDays(5),
                'amount_cents' => $approvedPayment->amount_cents,
                'metadata' => ['reference' => 'TRANS123456'],
                'payment_id' => $approvedPayment->id,
                'is_bs' => true,
            ]);

        Payment::factory()->create([
            'subscription_id' => $this->subscription->id,
            'status' => PaymentStatusEnum::Pending,
            'amount_cents' => $this->subscription->service_price_cents,
            'due_date' => now()->addDays(5),
            'is_bs' => true,
            'paid' => false,
        ]);
    }
}
