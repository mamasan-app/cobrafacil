<?php

namespace App\Filament\App\Pages;

use App\Enums\BankEnum;
use App\Enums\PhonePrefixEnum;
use App\Enums\SubscriptionStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\Payment;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Jobs\MonitorTransactionStatus;
use Exception;

class CreatePayment extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Gestión de Pagos';
    protected static string $view = 'filament.pages.subscription-payment';
    protected static ?string $title = 'Crear Pagos';

    public $subscription_id;
    public $otp;
    public $bank;
    public $phone;
    public $identity;
    public $amountInBs;
    public $payment;
    public $subscription;


    public function mount(): void
    {
        $this->resetForm();
    }


    public function resetForm(): void
    {
        $this->subscription_id = null;
        $this->otp = null;
        $this->bank = null;
        $this->phone = null;
        $this->identity = null;
        $this->amountInBs = null;
        $this->payment = null;
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('subscription_id')
                ->label('Suscripción')
                ->options(
                    Subscription::where(function ($query) {
                        $query->where('status', SubscriptionStatusEnum::OnTrial->value)
                            ->orWhereHas('payments', function ($query) {
                                $query->where('is_bs', true)
                                    ->where('status', PaymentStatusEnum::Pending);
                            });
                    })
                        ->whereNull('stripe_subscription_id')
                        ->get()
                        ->mapWithKeys(fn($sub) => [$sub->id => "{$sub->id} - {$sub->service_name}"])
                        ->toArray()

                )
                ->afterStateUpdated(fn($state) => $this->handleSubscriptionChange($state))
                ->required()
                ->reactive(),
        ];
    }

    public function handleSubscriptionChange($subscriptionId)
    {
        $this->subscription_id = $subscriptionId;

        if (!$subscriptionId) {
            return;
        }

        $this->subscription = Subscription::find($subscriptionId); // ✅ Guardar la suscripción

        if (!$this->subscription) {
            Notification::make()
                ->title('Error')
                ->body('No se encontró la suscripción seleccionada.')
                ->danger()
                ->send();
            return;
        }

        if ($this->subscription->status === SubscriptionStatusEnum::OnTrial->value) {
            $this->redirectRoute(
                'filament.app.resources.user-subscription-payment',
                ['record' => $subscriptionId]
            );
            return;
        }

        // Buscar el pago pendiente en Bs
        $this->payment = Payment::where('subscription_id', $subscriptionId)
            ->where('status', PaymentStatusEnum::Pending)
            ->where('is_bs', true)
            ->first();

        if (!$this->payment) {
            Notification::make()
                ->title('Error')
                ->body('No se encontró un pago pendiente en Bs para esta suscripción.')
                ->danger()
                ->send();
            return;
        }

        // Convertir el monto a Bs
        $amountInUsd = $this->payment->amount_cents / 100;
        $this->amountInBs = $this->convertToBs($amountInUsd) ?? $amountInUsd;
    }

    protected function getActions(): array
    {
        return [
            Action::make('payInBolivares')
                ->label('Pagar en Bolívares')
                ->modalHeading('Seleccionar una opción')
                ->modalWidth('lg')
                ->modalActions([

                    // Botón para registrar una nueva cuenta
                    Action::make('registerAccount')
                        ->label('Registrar cuenta y enviar')
                        ->color('secondary')
                        ->form([
                            Select::make('bank')
                                ->label('Banco')
                                ->options(
                                    collect(BankEnum::cases())
                                        ->mapWithKeys(fn($bank) => [$bank->code() => $bank->getLabel()])
                                        ->toArray()
                                )
                                ->required(),
                            Grid::make(2)
                                ->schema([
                                    Select::make('phone_prefix')
                                        ->label('Prefijo Telefónico')
                                        ->options(
                                            collect(PhonePrefixEnum::cases())
                                                ->mapWithKeys(fn($prefix) => [$prefix->value => $prefix->getLabel()])
                                                ->toArray()
                                        )
                                        ->required(),
                                    TextInput::make('phone_number')
                                        ->label('Número Telefónico')
                                        ->numeric()
                                        ->minLength(7)
                                        ->maxLength(7)
                                        ->required(),
                                ]),
                        ])
                        ->action(function (array $data) {
                            $user = auth()->user();

                            // Verificar si el usuario ya tiene cuentas registradas
                            $hasAccounts = $user->bankAccounts()->exists();

                            // Registrar la nueva cuenta
                            $newAccount = $user->bankAccounts()->create([
                                'bank_code' => $data['bank'],
                                'phone_number' => $data['phone_prefix'] . $data['phone_number'],
                                'identity_number' => str_replace('-', '', $user->identity_document),
                                'default_account' => !$hasAccounts, // Si no tiene cuentas, esta es la predeterminada
                            ]);

                            // Generar OTP para la nueva cuenta
                            $this->submitBolivaresPayment([
                                'bank' => $newAccount->bank_code,
                                'phone' => $newAccount->phone_number,
                                'identity' => $newAccount->identity_number,
                            ]);
                        })
                        ->hidden(fn() => $this->otp !== null),

                    // Botón para usar una cuenta existente
                    Action::make('useExistingAccount')
                        ->label('Realizar con cuenta existente')
                        ->color('primary')
                        ->form([
                            Select::make('existing_account')
                                ->label('Seleccionar Cuenta')
                                ->options(
                                    auth()->user()->bankAccounts()
                                        ->get()
                                        ->mapWithKeys(fn($account) => [
                                            $account->id => "{$account->bank_code} - {$account->phone_number} - {$account->identity_number}" .
                                                ($account->default_account ? ' (Predeterminada)' : ''),
                                        ])
                                        ->toArray()
                                )
                                ->default(
                                    auth()->user()->bankAccounts()
                                        ->where('default_account', true)
                                        ->first()?->id
                                )
                                ->required(),
                            TextInput::make('amountInBs')
                                ->label('Monto en Bolívares')
                                ->default($this->amountInBs)
                                ->disabled(),
                        ])
                        ->action(function (array $data) {
                            $bankAccount = auth()->user()->bankAccounts()->findOrFail($data['existing_account']);

                            $this->submitBolivaresPayment([
                                'bank' => $bankAccount->bank_code,
                                'phone' => $bankAccount->phone_number,
                                'identity' => $bankAccount->identity_number,
                            ]);
                        })
                        ->hidden(fn() => $this->otp !== null),

                    // Botón para confirmar OTP
                    Action::make('confirmOtp')
                        ->label('Confirmar OTP')
                        ->color('info')
                        ->form([
                            TextInput::make('otp')
                                ->label('Código OTP')
                                ->required(),
                        ])
                        ->action(function (array $data) {
                            $this->otp = $data['otp'];

                            // Confirmar OTP
                            $this->confirmOtp([
                                'bank' => $this->bank,
                                'phone' => $this->phone,
                                'identity' => $this->identity,
                                'amount' => $this->amountInBs,
                                'otp' => $this->otp,
                            ]);
                        })
                        ->visible(fn() => $this->otp !== null),
                ]),
        ];
    }

    public function submitBolivaresPayment(array $data)
    {
        $this->bank = $data['bank'];
        $this->phone = $data['phone'];
        $this->identity = $data['identity'];

        try {
            $otpResponse = $this->generateOtp();
            //dd($otpResponse);

            if (!isset($otpResponse['success']) || !$otpResponse['success']) {
                Notification::make()
                    ->title('Error')
                    ->body('No se pudo generar el OTP. Intente nuevamente.')
                    ->danger()
                    ->send();

                return;
            }

            // OTP generado correctamente
            $this->otp = true; // Se asegura de que el OTP esté listo para confirmarse.
            Notification::make()
                ->title('OTP Generado')
                ->body('Se ha enviado un código OTP a tu teléfono. Por favor, ingrésalo para continuar.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Interno')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function generateOtp()
    {
        // Transformar todos los valores a string
        $bank = (string) $this->bank;
        $amount = (string) number_format((float) $this->amountInBs, 2, '.', ''); // Convertir a string con dos decimales
        $phone = (string) $this->phone;
        $identity = (string) $this->identity;

        // Concatenar los datos para el HMAC-SHA256
        $stringToHash = "{$bank}{$amount}{$phone}{$identity}";
        // dd('String a Hashear', $stringToHash);

        // Generar el token HMAC-SHA256
        $tokenAuthorization = hash_hmac(
            'sha256',
            $stringToHash,
            config('banking.commerce_id') // Llave secreta desde configuración
        );

        // Enviar la solicitud HTTP
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $tokenAuthorization,
            'Commerce' => config('banking.commerce_id'), // Verificar este valor en la configuración
        ])->post(config('banking.otp_url'), [
                    'Banco' => $bank, // Código del banco (4 dígitos)
                    'Monto' => $amount, // Cadena con dos decimales
                    'Telefono' => $phone, // Teléfono completo (11 dígitos)
                    'Cedula' => $identity, // Cédula con prefijo
                ]);
        //dd('Respuesta de la API', $response->json());

        return $response->json();
    }

    public function confirmOtp(array $data)
    {
        $this->otp = $data['otp']; // Asignar el OTP ingresado por el usuario.

        if ($this->otp === null) {
            Notification::make()
                ->title('Error')
                ->body('Debe ingresar un OTP para confirmar el pago.')
                ->danger()
                ->send();

            return;
        }

        try {
            // Procesar el débito inmediato y obtener el ID de la transacción
            $immediateDebitResponse = $this->processImmediateDebit($this->payment);

            // Verificar si se generó correctamente un ID de transacción
            if (isset($immediateDebitResponse['id'])) {
                // Despachar el Job para monitorear el estado de la transacción
                MonitorTransactionStatus::dispatch($immediateDebitResponse['id']);

                Notification::make()
                    ->title('Proceso Iniciado')
                    ->body('El pago está siendo procesado. Recibirás una notificación cuando el proceso sea completado.')
                    ->info()
                    ->send();
            } else {
                throw new \Exception('No se pudo iniciar el proceso de pago. Inténtelo nuevamente.');
            }

            // Limpiar el OTP después de iniciar el proceso
            $this->otp = null;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Interno')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function processImmediateDebit($payment)
    {
        $user = auth()->user(); // Obtener el usuario autenticado
        $store = $this->subscription ? $this->subscription->store : null;

        if (!$store) {
            throw new Exception('No se pudo obtener la tienda de la suscripción.');
        }

        $nombre = $user->name ?? "{$user->first_name} {$user->last_name}"; // Obtener el nombre completo
        $bank = (string) $this->bank;
        $amount = (string) number_format((float) $this->amountInBs, 2, '.', ''); // Convertir a string con dos decimales
        $phone = (string) $this->phone;
        $identity = (string) $this->identity;
        $otp = (string) $this->otp;

        $stringToHash = "{$bank}{$identity}{$phone}{$amount}{$otp}";

        $tokenAuthorization = hash_hmac(
            'sha256',
            $stringToHash,
            config('banking.commerce_id')
        );

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => $tokenAuthorization,
            'Commerce' => config('banking.commerce_id'),
        ])->post(config('banking.debit_url'), [
                    'Banco' => $bank,
                    'Monto' => $amount,
                    'Telefono' => $phone,
                    'Cedula' => $identity,
                    'Nombre' => $nombre,
                    'Concepto' => 'pago de suscripcion',
                    'OTP' => $otp,
                ]);

        Transaction::create([
            'from_type' => get_class($user),
            'from_id' => $user->id,
            'to_type' => get_class($store),
            'to_id' => $store->id,
            'type' => TransactionTypeEnum::Subscription->value,
            'status' => TransactionStatusEnum::Processing,
            'date' => now()->setTimezone('America/Caracas'),
            'amount_cents' => $amount * 100,
            'metadata' => $response->json(),
            'payment_id' => $payment->id,
            'is_bs' => true,
        ]);

        return $response->json();

    }

    protected function convertToBs($amountInUSD)
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $this->generateBcvToken(),
                'Commerce' => config('banking.commerce_id'),
            ])->post(config('banking.tasa_bcv'), [
                        'Moneda' => 'USD',
                        'Fechavalor' => now()->format('Y-m-d'),
                    ]);

            $rate = $response->json()['tipocambio'] ?? null;

            //dd($response->json());

            if ($rate) {
                return round($amountInUSD * $rate, 2);
            }

            throw new Exception('No se pudo obtener la tasa de cambio.');
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al obtener la tasa')
                ->body('No se pudo obtener la tasa de cambio del BCV. Detalles: ' . $e->getMessage())
                ->danger()
                ->send();

            return null;
        }
    }

    protected function generateBcvToken()
    {
        $data = now()->format('Y-m-d') . 'USD';

        return hash_hmac('sha256', $data, config('banking.commerce_id'));
    }

}
