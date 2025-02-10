<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatusEnum;
use App\Enums\SubscriptionStatusEnum;
use App\Jobs\SendSubscriptionReminderEmail;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Console\Command;

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
            // Crear pago pendiente si no existe uno para este ciclo de facturación
            $existingPayment = Payment::where('subscription_id', $subscription->id)
                ->where('status', PaymentStatusEnum::Pending)
                ->whereBetween('due_date', [$today, $sevenDaysLater])
                ->exists();

            if (! $existingPayment) {
                $payment = Payment::create([
                    'subscription_id' => $subscription->id,
                    'status' => PaymentStatusEnum::Pending,
                    'amount_cents' => $subscription->service_price_cents,
                    'due_date' => $subscription->renews_at,
                    'is_bs' => true,
                ]);

                $this->info("Pago pendiente creado para la suscripción ID: {$subscription->id} con monto: {$payment->amount_cents} centavos.");
            }

            // Despachar el job de notificación
            dispatch(new SendSubscriptionReminderEmail($subscription));

            $this->info("Recordatorio enviado a usuario con ID: {$subscription->user_id}");
        }

        $this->info('Se han procesado todas las suscripciones.');
    }
}
