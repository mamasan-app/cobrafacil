<?php

namespace App\Services;

use App\Enums\BankEnum;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class R4Service
{
    protected string $baseUrl;

    protected string $token;

    protected string $commerceId;

    public function __construct()
    {
        $this->baseUrl = config('services.r4.base_url');
        $this->token = config('services.r4.token');
        $this->commerceId = config('services.r4.commerce_id');
    }

    public static function new(): self
    {
        return new self;
    }

    public function generateOtp(BankEnum $bank, string $bsAmount, string $phoneNumber, string $identityDocument): array
    {
        $stringToHash = "{$bank->value}{$bsAmount}{$phoneNumber}{$identityDocument}";

        $authToken = hash_hmac(
            'sha256',
            $stringToHash,
            $this->commerceId
        );

        $response = $this->request()
            ->withToken($authToken)
            ->withHeaders([
                'Commerce' => $this->commerceId,
            ])->post('/GenerarOtp', [
                'Banco' => $bank->value,
                'MontoBs' => $bsAmount,
                'Telefono' => $phoneNumber,
                'DocumentoIdentidad' => $identityDocument,
            ])->json();

        return $response;
    }

    public function inmediateDebit(BankEnum $bank, string $bsAmount, string $phoneNumber, string $identityDocument, string $otp, string $name): array
    {
        $stringToHash = "{$bank->value}{$bsAmount}{$phoneNumber}{$identityDocument}{$otp}";

        $authToken = hash_hmac(
            'sha256',
            $stringToHash,
            $this->commerceId
        );

        $response = $this->request()
            ->withToken($authToken)
            ->withHeaders([
                'Commerce' => $this->commerceId,
            ])->post('/DebitoInmediato', [
                'Banco' => $bank->value,
                'Monto' => $bsAmount,
                'Telefono' => $phoneNumber,
                'Cedula' => $identityDocument,
                'Nombre' => $name,
                'Concepto' => 'Pago de Servicio',
                'OTP' => $otp,
            ])->json();

        return $response;
    }

    public function getBcvRate(): array
    {
        $currency = 'USD';
        $date = now('America/Caracas')->format('Y-m-d');
        $authToken = hash_hmac('sha256', "{$date}{$currency}", $this->commerceId);

        $response = $this->request()
            ->withHeaders([
                'Commerce' => $this->commerceId,
            ])
            ->withToken($authToken)
            ->post('/MBbcv', [
                'Moneda' => 'USD',
                'Fechavalor' => $date,
            ])
            ->json();

        return $response;
    }

    protected function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->throw()
            ->asJson()
            ->acceptJson();
    }
}
