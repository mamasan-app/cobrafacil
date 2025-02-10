<?php

namespace App\Jobs;

use App\Enums\PaymentStatusEnum;
use App\Enums\SubscriptionStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Models\Plan;
use App\Models\Store;
use App\Models\Subscription;
use App\Models\Transaction;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class MonitorTransactionStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $operationId;

    /**
     * Create a new job instance.
     *
     * @param  string  $operationId
     */
    public function __construct($operationId)
    {
        $this->operationId = $operationId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            // Obtener la transacción asociada al operationId
            $transaction = Transaction::where('metadata->id', $this->operationId)
                ->where('is_bs', true)
                ->first();

            if (! $transaction) {
                throw new \Exception("Transacción no encontrada para el ID de operación: {$this->operationId}");
            }

            // Consultar estado de la operación
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => hash_hmac(
                    'sha256',
                    $this->operationId,
                    config('banking.commerce_id')
                ),
                'Commerce' => config('banking.commerce_id'),
            ])->post(config('banking.consult_debit'), [
                'Id' => $this->operationId,
            ]);

            $statusCode = $response->json()['code'] ?? null;

            if ($statusCode === 'AC00') {
                // Transacción en proceso: no hacer nada
                return;
            }

            $payment = $transaction->payment;
            $subscription = Subscription::find($payment->subscription_id);

            $currentDate = now()->setTimezone('America/Caracas');

            if ($statusCode === 'ACCP') {

                $transaction->update([
                    'status' => TransactionStatusEnum::Succeeded,
                    'metadata' => $response->json(),
                ]);

                $payment->update([
                    'status' => PaymentStatusEnum::Completed,
                ]);

                // **Validar que la transacción esté asociada a una tienda**
                if ($transaction->to_type === Store::class) {
                    $store = Store::find($transaction->to_id);

                    if ($store && $store->bank_account_default) {
                        $montoTransaction = $transaction->amount;
                        $montoVuelto = $montoTransaction - ($montoTransaction * 0.03);

                        dispatch(new ProcessRefundJob($transaction, $montoVuelto, $store));
                    }
                }

                if ($subscription && $subscription->isOnTrial()) {
                    // Calcular las fechas de forma independiente
                    $renewDate = $currentDate->copy()->addDays($subscription->frequency_days)->toDateString();
                    $expireDate = $currentDate->copy()->addDays($subscription->frequency_days + $subscription->service_grace_period)->toDateString();

                    $plan = Plan::find($subscription->service_id);

                    if ($plan) {
                        if (! $plan->infinite_duration) {
                            // Plan finito: calcular la fecha de expiración
                            $endDate = $currentDate->copy()->addDays($plan->duration)->toDateString();

                            $subscription->update([
                                'status' => SubscriptionStatusEnum::Active,
                                'trial_ends_at' => $currentDate->toDateString(), // Finaliza el periodo de prueba
                                'renews_at' => $renewDate,
                                'expires_at' => $expireDate,
                                'ends_at' => $endDate,
                            ]);

                            Notification::make()
                                ->title('Suscripción activada')
                                ->body("La suscripción ha sido activada con fecha de expiración: $endDate.")
                                ->success()
                                ->send();
                        } else {
                            // Plan infinito: no tiene fecha de expiración
                            $subscription->update([
                                'status' => SubscriptionStatusEnum::Active,
                                'trial_ends_at' => $currentDate->copy()->toDateString(), // Finaliza el periodo de prueba
                                'renews_at' => $renewDate,
                                'expires_at' => null, // Infinito
                                'ends_at' => null,
                            ]);

                            Notification::make()
                                ->title('Suscripción activada')
                                ->body('La suscripción ha sido activada sin fecha de expiración.')
                                ->success()
                                ->send();
                        }
                    } else {
                        throw new \Exception("Plan no encontrado para la suscripción: {$subscription->id}");
                    }
                } elseif ($subscription && ! $subscription->isOnTrial) {
                    $renewDate = $subscription->renews_at->copy()->addDays($subscription->frequency_days)->toDateString();
                    $expireDate = $subscription->renews_at->copy()->addDays($subscription->frequency_days + $subscription->service_grace_period)->toDateString();

                    $subscription->update([
                        'status' => SubscriptionStatusEnum::Active,
                        'renews_at' => $renewDate,
                        'expires_at' => $expireDate,
                    ]);
                }
            } else {
                $transaction->update([
                    'status' => TransactionStatusEnum::Failed,
                    'metadata' => $response->json(),
                ]);

                $payment->update([
                    'status' => PaymentStatusEnum::Failed,
                ]);

                if ($subscription && ! $subscription->isOnTrial && $currentDate->copy()->greaterThanOrEqualTo($subscription->expires_at)) {
                    $subscription->update([
                        'status' => SubscriptionStatusEnum::Cancelled,
                    ]);
                } elseif ($subscription && $subscription->isActive && $currentDate->copy()->greaterThan($subscription->renews_at)) {

                    if ($currentDate->copy()->lessThan($subscription->expires_at)) {
                        $subscription->update([
                            'status' => SubscriptionStatusEnum::PastDue,
                        ]);
                    } else {
                        $subscription->update([
                            'status' => SubscriptionStatusEnum::Cancelled,
                        ]);
                    }
                }

                Notification::make()
                    ->title('Pago fallido')
                    ->body('El primer pago no pudo completarse. Verifica la transacción o inténtalo nuevamente.')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error en la verificación de la transacción')
                ->body('Detalles: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }
}
