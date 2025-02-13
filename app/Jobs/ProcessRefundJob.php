<?php

namespace App\Jobs;

use App\Models\Store;
use App\Models\Transaction;
use App\Models\Store;
use App\Models\Payment; // Importar el modelo Payment
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Notifications\RefundSuccessfulNotification;
use Illuminate\Support\Facades\Notification;

class ProcessRefundJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transaction;

    protected $montoVuelto;

    protected $store;

    public function __construct(Transaction $transaction, float $montoVuelto, Store $store)
    {
        $this->transaction = $transaction;
        $this->montoVuelto = $montoVuelto;
        $this->store = $store;
    }

    public function handle()
    {
        if (!$this->store->defaultBankAccount()) {
            Log::error("La tienda no tiene una cuenta bancaria predeterminada.");
            return;
        }

        $bankAccount = $this->store->defaultBankAccount();

        $bank = (string) $bankAccount->bank_code;
        $amount = (string) number_format((float) $this->montoVuelto, 2, '.', ''); // Convertir a string con dos decimales
        $phone = (string) $bankAccount->phone_number;
        $identity = (string) $bankAccount->identity_number;

        Log::info("Enviando solicitud de vuelto", [
            'TelefonoDestino' => $phone,
            'Cedula' => $identity,
            'Banco' => $bank,
            'Monto' => $amount
        ]);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => hash_hmac(
                'sha256',
                $phone . $amount . $bank . $identity,
                config('banking.commerce_id')
            ),
            'Commerce' => config('banking.commerce_id'),
        ])->post(config('banking.vuelto_url'), [
                    'TelefonoDestino' => $phone,
                    'Cedula' => $identity,
                    'Banco' => $bank,
                    'Monto' => $amount,
                    'Concepto' => 'Vuelto',
                    'Ip' => request()->ip() ?? '0.0.0.0',
                ]);

        $responseData = $response->json();
        Log::info("Respuesta del servicio MBvuelto", ['response' => $responseData]);

        // Determinar el estado de la transacción
        $status = ($responseData['code'] === "00") ? 'succeeded' : 'failed';

        // Crear la transacción
        $refundTransaction = Transaction::create([
            'from_type' => Transaction::class,
            'from_id' => $this->transaction->id,
            'to_type' => Store::class,
            'to_id' => $this->store->id,
            'type' => 'refund',
            'status' => $status,
            'amount_cents' => $this->montoVuelto * 100,
            'metadata' => $responseData,
            'is_bs' => true,
            'payment_id' => $this->transaction->payment_id,
            'date' => now()->setTimezone('America/Caracas'),
        ]);

        Log::info("Transacción de reembolso creada", [
            'transaction_id' => $refundTransaction->id,
            'status' => $status
        ]);

        // Si el reembolso fue exitoso, actualizar el pago asociado
        if ($status === 'succeeded') {
            $payment = Payment::where('id', $this->transaction->payment_id)->first();

            if ($payment) {
                $payment->update(['paid' => true]);
                Log::info("Pago actualizado como pagado", ['payment_id' => $payment->id]);

                // Enviar notificación a la tienda
                $this->store->notify(new RefundSuccessfulNotification($this->store, $this->montoVuelto));
                Log::info("Notificación de reembolso enviado a la tienda", ['store_id' => $this->store->id]);
            } else {
                Log::error("No se encontró el pago asociado a la transacción.", [
                    'transaction_id' => $this->transaction->id
                ]);
            }
        }
    }
}
