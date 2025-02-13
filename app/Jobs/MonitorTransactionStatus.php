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
use Illuminate\Support\Facades\Log;

class MonitorTransactionStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $operationId;

    public function __construct($operationId)
    {
        $this->operationId = $operationId;
    }

    public function handle()
    {
        try {
            Log::info("Iniciando MonitorTransactionStatus para operationId: {$this->operationId}");

            // Obtener la transacción asociada al operationId
            $transaction = Transaction::where('metadata->id', $this->operationId)
                ->where('is_bs', true)
                ->first();

            if (! $transaction) {
                Log::error("Transacción no encontrada para operationId: {$this->operationId}");
                throw new \Exception("Transacción no encontrada para el ID de operación: {$this->operationId}");
            }

            Log::info('Transacción encontrada: ', ['id' => $transaction->id, 'monto' => $transaction->amount]);

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
            Log::info("Código de estado de la transacción: $statusCode");

            if ($statusCode === 'AC00') {
                Log::info('Transacción en proceso, no se ejecutará ninguna acción.');

                return;
            }

            $payment = $transaction->payment;
            $subscription = Subscription::find($payment->subscription_id);

            $currentDate = now()->setTimezone('America/Caracas');

            if ($statusCode === 'ACCP') {
                Log::info('Transacción aprobada, actualizando estados.');

                $transaction->update([
                    'status' => TransactionStatusEnum::Succeeded,
                    'metadata' => $response->json(),
                ]);

                $payment->update([
                    'status' => PaymentStatusEnum::Completed,
                ]);

                // **Validar que la transacción esté asociada a una tienda**
                if ($transaction->to_type === Store::class) {
                    Log::info('Transacción asociada a una tienda, obteniendo Store.');

                    $store = $transaction->store;

                    if ($store) {
                        Log::info('Store encontrada: ', ['id' => $store->id, 'nombre' => $store->name]);
                    } else {
                        Log::error('No se encontró Store para la transacción.');
                    }

                    if ($store) {
                        Log::info('Store tiene una cuenta bancaria por defecto.');

                        $montoVuelto = $transaction->amount - ($transaction->amount * 0.03);

                        Log::info("Monto de la transacción: $transaction->amount | Monto del vuelto: $montoVuelto");

                        $bankAccount = $store->defaultBankAccount();

                        if ($bankAccount) {
                            Log::info('Store tiene una cuenta bancaria por defecto.', ['bank_account_id' => $bankAccount->id]);

                            dispatch(new ProcessRefundJob($transaction, $montoVuelto, $store));
                            Log::info('ProcessRefundJob enviado correctamente.');
                        }

                    } else {
                        Log::error('No se pudo procesar el reembolso, la tienda no tiene cuenta bancaria predeterminada.');
                    }
                }

                // Procesar suscripción
                if ($subscription) {
                    Log::info('Procesando suscripción.');

                    if ($subscription->isOnTrial()) {
                        $renewDate = $currentDate->copy()->addDays($subscription->frequency_days)->toDateString();
                        $expireDate = $currentDate->copy()->addDays($subscription->frequency_days + $subscription->service_grace_period)->toDateString();

                        $plan = Plan::find($subscription->service_id);

                        if ($plan) {
                            Log::info('Plan encontrado para la suscripción.');

                            if (! $plan->infinite_duration) {
                                $endDate = $currentDate->copy()->addDays($plan->duration)->toDateString();
                                $subscription->update([
                                    'status' => SubscriptionStatusEnum::Active,
                                    'trial_ends_at' => $currentDate->toDateString(),
                                    'renews_at' => $renewDate,
                                    'expires_at' => $expireDate,
                                    'ends_at' => $endDate,
                                ]);
                                Log::info("Suscripción activada con fecha de expiración: $endDate.");
                            } else {
                                $subscription->update([
                                    'status' => SubscriptionStatusEnum::Active,
                                    'trial_ends_at' => $currentDate->copy()->toDateString(),
                                    'renews_at' => $renewDate,
                                    'expires_at' => null,
                                    'ends_at' => null,
                                ]);
                                Log::info('Suscripción activada sin fecha de expiración.');
                            }
                        } else {
                            Log::error("Plan no encontrado para la suscripción: {$subscription->id}");
                        }
                    } else {
                        $renewDate = $subscription->renews_at->copy()->addDays($subscription->frequency_days)->toDateString();
                        $expireDate = $subscription->renews_at->copy()->addDays($subscription->frequency_days + $subscription->service_grace_period)->toDateString();

                        $subscription->update([
                            'status' => SubscriptionStatusEnum::Active,
                            'renews_at' => $renewDate,
                            'expires_at' => $expireDate,
                        ]);
                        Log::info('Suscripción renovada.');
                    }
                }
            } else {
                Log::error('Transacción fallida, actualizando estado.');

                $transaction->update([
                    'status' => TransactionStatusEnum::Failed,
                    'metadata' => $response->json(),
                ]);

                $payment->update([
                    'status' => PaymentStatusEnum::Failed,
                ]);

                Notification::make()
                    ->title('Pago fallido')
                    ->body('El primer pago no pudo completarse. Verifica la transacción o inténtalo nuevamente.')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Log::error('Error en la verificación de la transacción: '.$e->getMessage());

            Notification::make()
                ->title('Error en la verificación de la transacción')
                ->body('Detalles: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }
}
