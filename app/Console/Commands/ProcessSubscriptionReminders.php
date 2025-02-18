<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatusEnum;
use App\Enums\SubscriptionStatusEnum;
use App\Jobs\SendSubscriptionReminderEmail;
use App\Models\Payment;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessSubscriptionReminders extends Command
{
    /**
     * El nombre y la firma del comando Artisan.
     *
     * @var string
     */
    protected $signature = 'subscriptions:send-reminders';

    /**
     * La descripción del comando.
     *
     * @var string
     */
    protected $description = 'Envía recordatorios de pago para suscripciones activas sin Stripe y crea pagos pendientes.';

    /**
     * Ejecuta el comando.
     */
    public function handle()
    {
        $today = now()->setTimezone('America/Caracas');
        $sevenDaysLater = $today->copy()->addDays(7);

        // Obtener suscripciones activas sin Stripe y con renovación en <= 7 días
        $subscriptions = Subscription::where('status', SubscriptionStatusEnum::Active)
            ->whereNull('stripe_subscription_id')
            ->whereBetween('renews_at', [$today, $sevenDaysLater])
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->info('No hay suscripciones que requieran recordatorio.');

            return;
        }

        foreach ($subscriptions as $subscription) {
            if (! $subscription->hasBsPayment()) {
                $this->info("La suscripción ID: {$subscription->id} no tiene pagos en Bs, se omite del proceso.");

                continue;
            }

            // Obtener el plan asociado a la suscripción
            $plan = Plan::find($subscription->service_id);

            if ($plan) {
                Log::info("Plan encontrado para la suscripción ID: {$subscription->id}");

                // Si el plan no es infinito y la suscripción está en su último ciclo, omitir
                if (! $plan->infinite_duration && $subscription->renews_at->equalTo($subscription->ends_at)) {
                    $this->info("La suscripción ID: {$subscription->id} está en su último ciclo y no se renovará.");

                    continue;
                }
            } else {
                Log::warning("No se encontró el plan para la suscripción ID: {$subscription->id}");
            }

            // Verificar si ya hay pagos pendientes
            $existingPayment = Payment::where('subscription_id', $subscription->id)
                ->where('status', PaymentStatusEnum::Pending)
                ->exists();

            if (! $existingPayment) {
                Payment::create([
                    'subscription_id' => $subscription->id,
                    'status' => PaymentStatusEnum::Pending,
                    'amount_cents' => $subscription->service_price_cents,
                    'due_date' => $subscription->renews_at,
                    'is_bs' => true,
                ]);

                $this->info("Pago pendiente creado para la suscripción ID: {$subscription->id}.");
            }

            // Enviar notificación
            dispatch(new SendSubscriptionReminderEmail($subscription));

            $this->info("Recordatorio enviado a usuario con ID: {$subscription->user_id}");
        }

        $this->info('Se han procesado todas las suscripciones.');
    }
}
