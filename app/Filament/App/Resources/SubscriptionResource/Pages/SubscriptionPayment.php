<?php

namespace App\Filament\App\Resources\SubscriptionResource\Pages;

use App\Enums\BankEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Filament\App\Resources\SubscriptionResource;
use App\Filament\Inputs;
use App\Jobs\MonitorTransactionStatus;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Services\StripeService;
use Exception;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Actions\Action;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Http;

class SubscriptionPayment extends Page
{
    protected static string $resource = SubscriptionResource::class;

    protected static string $view = 'filament.pages.user-subscription-payment';

    public function getTitle(): string
    {
        return 'Primer Pago';
    }

    public Subscription $subscription;

    public $bank;

    public $phone;

    public $identity;

    public $amount;

    public $otp = null;

    public $amountInBs;

    public function mount($record): void
    {
        /** @var Subscription */
        $this->subscription = Subscription::findOrFail($record);
        $this->amount = $this->subscription->service_price_cents / 100;
        $this->amountInBs = $this->convertToBs($this->amount);
        $this->otp = null;

        if ($filter = request()->query('success') === '1') {
            Notification::make()
                ->title('Pago exitoso')
                ->body('Tu suscripción se activó correctamente.')
                ->success()
                ->send();
            redirect(SubscriptionResource::getUrl('index'));
        } elseif ($filter = request()->query('success') === '0') {
            Notification::make()
                ->title('Pago cancelado')
                ->body('No se pudo completar el pago de tu suscripción. Inténtalo nuevamente.')
                ->danger()
                ->send();
        }
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

            // dd($response->json());

            if ($rate) {
                return round($amountInUSD * $rate, 2);
            }

            throw new Exception('No se pudo obtener la tasa de cambio.');
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al obtener la tasa')
                ->body('No se pudo obtener la tasa de cambio del BCV. Detalles: '.$e->getMessage())
                ->danger()
                ->send();

            return null;
        }
    }

    protected function generateBcvToken()
    {
        $data = now()->format('Y-m-d').'USD';

        return hash_hmac('sha256', $data, config('banking.commerce_id'));
    }

    public function createStripeSession(StripeService $stripeService)
    {
        try {
            // Obtener o crear el cliente en Stripe
            $customer = $stripeService->getOrCreateCustomer($this->subscription->user);

            // Obtener o crear el producto en Stripe
            $product = $stripeService->getOrCreateProduct($this->subscription->service);

            // Determinar el intervalo y el intervalo_count
            $frequency_days = $this->subscription->frequency_days;
            [$interval, $intervalCount] = $this->getIntervalDetails($frequency_days);

            // Crear el precio en Stripe
            $price = $stripeService->createPrice(
                $product,
                $this->subscription->service_price_cents,
                $interval,
                $intervalCount,
                $this->subscription->service_grace_period,
            );

            // Crear la sesión de Stripe Checkout
            $session = $stripeService->createCheckoutSession(
                $customer,
                $price,
                static::getResource()::getUrl('payment', [
                    'record' => $this->subscription->id,
                    'success' => true,
                ]),
                static::getResource()::getUrl('payment', [
                    'record' => $this->subscription->id,
                    'success' => false,
                ]),
                [
                    'payment_id' => $this->subscription->id,
                    'subscription_id' => $this->subscription->id,
                ]
            );

            if (isset($session->subscription)) {
                $this->subscription->update([
                    'stripe_subscription_id' => $session->subscription,
                ]);
            }

            return redirect($session->url);
        } catch (\Exception $e) {
            // Mostrar notificación de error
            Notification::make()
                ->title('Error al crear la sesión de Stripe')
                ->body('Ocurrió un problema al crear la sesión de Stripe. Detalles: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function getIntervalDetails($frequency_days)
    {
        if ($frequency_days < 7) {
            return ['day', $frequency_days];
        } elseif ($frequency_days % 7 === 0 && $frequency_days < 28) {
            return ['week', $frequency_days / 7];
        } elseif ($frequency_days % 30 === 0) {
            return ['month', $frequency_days / 30];
        } elseif ($frequency_days % 365 === 0) {
            return ['year', $frequency_days / 365];
        }

        throw new Exception('La frecuencia no es compatible con los intervalos permitidos por Stripe.');
    }

    public function submitBolivaresPayment(array $data)
    {
        $this->bank = $data['bank'];
        $this->phone = $data['phone'];
        $this->identity = $data['identity'];

        try {
            $otpResponse = $this->generateOtp();
            // dd($otpResponse);

            if (! isset($otpResponse['success']) || ! $otpResponse['success']) {
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
        $bank = $this->bank->value;
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
        // dd('Respuesta de la API', $response->json());

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
            // Crear el pago en estado 'pending'
            $payment = Payment::create([
                'subscription_id' => $this->subscription->id,
                'status' => \App\Enums\PaymentStatusEnum::Pending,
                'amount_cents' => $this->subscription->service_price_cents,
                'due_date' => $this->subscription->ends_at,
                'is_bs' => true,
            ]);

            // Procesar el débito inmediato y obtener el ID de la transacción
            $immediateDebitResponse = $this->processImmediateDebit($payment);

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
        $store = $this->subscription->store; // Tienda asociada a la suscripción

        $nombre = $user->name ?? "{$user->first_name} {$user->last_name}"; // Obtener el nombre completo
        $bank = $this->bank->value;
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

    protected function getActions(): array
    {
        return [
            Action::make('payInUSD')
                ->label('Pagar en USD')
                ->color('success')
                ->action(function () {
                    $stripeService = app(StripeService::class); // Inyectar StripeService
                    $this->createStripeSession($stripeService);
                }),

            Action::make('payInBolivares')
                ->label('Pagar en Bolívares')
                ->modalHeading('Seleccionar una opción')
                ->modalWidth('lg')
                ->modalActions([
                    Action::make('registerAccount')
                        ->label('Registrar cuenta y enviar')
                        ->color('gray')
                        ->form([
                            Select::make('bank')
                                ->label('Banco')
                                ->options(BankEnum::class)
                                ->required(),

                            Inputs\PhoneNumberInput::make()
                                ->label('Número de teléfono')
                                ->required(),

                            Inputs\IdentityPrefixSelect::make()
                                ->required(),

                            Inputs\IdentityNumberInput::make()
                                ->required(),
                        ])
                        ->action(function (array $data) {
                            $user = auth()->user();

                            // Verificar si el usuario ya tiene cuentas registradas
                            $hasAccounts = $user->bankAccounts()->exists();

                            // Registrar la nueva cuenta
                            $newAccount = $user->bankAccounts()->create([
                                'bank_code' => $data['bank'],
                                'phone_number' => $data['phone_number'],
                                'identity_prefix' => $data['identity_prefix'],
                                'identity_number' => $data['identity_number'],
                                'default_account' => ! $hasAccounts,
                            ]);

                            // Generar OTP para la nueva cuenta
                            $this->submitBolivaresPayment([
                                'bank' => $newAccount->bank_code,
                                'phone' => $newAccount->phone_number,
                                'identity' => $newAccount->identity_number,
                            ]);
                        })
                        ->hidden(fn () => $this->otp !== null), // Ocultar este botón si el OTP fue generado.

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
                                        ->mapWithKeys(fn ($account) => [
                                            $account->id => "{$account->bank_code} - {$account->phone_number} - {$account->identity_number}".
                                                ($account->default_account ? ' (Predeterminada)' : ''),
                                        ])
                                        ->toArray()
                                )
                                ->default(
                                    auth()->user()->bankAccounts()
                                        ->where('default_account', true)
                                        ->first()?->id // Seleccionar la cuenta predeterminada como valor inicial
                                )
                                ->required(),
                            TextInput::make('amountInBs')
                                ->label('Monto en Bolívares')
                                ->default($this->amountInBs) // Usa default() para establecer el valor inicial
                                ->disabled(), // Deshabilitar para que sea de solo lectura
                        ])
                        ->action(function (array $data) {
                            $bankAccount = auth()->user()->bankAccounts()->findOrFail($data['existing_account']);

                            $this->submitBolivaresPayment([
                                'bank' => $bankAccount->bank_code,
                                'phone' => $bankAccount->phone_number,
                                'identity' => $bankAccount->identity_number,
                            ]);
                        })
                        ->hidden(fn () => $this->otp !== null), // Ocultar este botón si el OTP fue generado.

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
                            $this->otp = $data['otp']; // Asignar el OTP ingresado por el usuario.

                            // Aquí puedes usar los datos del submitBolivaresPayment:
                            $this->confirmOtp([
                                'bank' => $this->bank,
                                'phone' => $this->phone,
                                'identity' => $this->identity,
                                'amount' => $this->amountInBs,
                                'otp' => $this->otp,
                            ]);
                        })
                        ->visible(fn () => $this->otp !== null), // Mostrar solo si el OTP ha sido generado.
                ]),
        ];
    }
}
