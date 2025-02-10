<?php

namespace App\Jobs;

use App\Models\Store;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

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
        if (! $this->store->bank_account_default) {
            return;
        }

        $bankAccount = $this->store->bank_account_default;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => hash_hmac(
                'sha256',
                $bankAccount->telefono.$this->montoVuelto.$bankAccount->banco.$bankAccount->cedula,
                config('banking.commerce_token')
            ),
            'Commerce' => config('banking.commerce_id'),
        ])->post(config('banking.vuelto_url'), [
            'TelefonoDestino' => $bankAccount->telefono,
            'Cedula' => $bankAccount->cedula,
            'Banco' => $bankAccount->banco,
            'Monto' => number_format($this->montoVuelto, 2, '.', ''),
            'Concepto' => 'Vuelto',
            'Ip' => request()->ip(),
        ]);

        if ($response->successful()) {
            Transaction::create([
                'from_type' => null,
                'from_id' => null,
                'to_type' => Store::class,
                'to_id' => $this->store->id,
                'type' => 'refund',
                'status' => 'succeeded',
                'amount_cents' => $this->montoVuelto * 100,
                'metadata' => $response->json(),
                'is_bs' => true,
            ]);
        }
    }
}
