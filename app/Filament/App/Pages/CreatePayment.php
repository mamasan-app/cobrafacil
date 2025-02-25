<?php

namespace App\Filament\App\Pages;

use App\Enums\BankEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\SubscriptionStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Filament\App\Resources\SubscriptionResource\Pages\SubscriptionPayment;
use App\Filament\Inputs;
use App\Jobs\MonitorTransactionStatus;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Transaction;
use Exception;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;

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
                    Subscription::whereHas('payments', function ($query) {
                        return $query->where('is_bs', true)
                            ->where('status', PaymentStatusEnum::Pending);
                    })
                        ->where('user_id', auth()->id())
                        ->whereNull('stripe_subscription_id')
                        ->get()
                        ->mapWithKeys(fn (Subscription $sub) => [$sub->id => "{$sub->store->name} | {$sub->service_name}"])
                        ->toArray()

                )
                ->afterStateUpdated(fn ($state) => $this->handleSubscriptionChange($state))
                ->required()
                ->reactive(),
        ];
    }

    public function handleSubscriptionChange($subscriptionId)
    {
        $this->subscription_id = $subscriptionId;

        if (! $subscriptionId) {
            return;
        }

        $this->subscription = Subscription::find($subscriptionId); // ✅ Guardar la suscripción

        if (! $this->subscription) {
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

        if (! $this->payment) {
            redirect(SubscriptionPayment::getUrl(['record' => $this->subscription]));

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

                    Action::make('registerAccount')
                        ->label('Registrar cuenta y enviar')
                        ->color('gray')
                        ->form([
                            Forms\Components\Select::make('bank_code')
                                ->label('Banco')
                                ->options(BankEnum::class)
                                ->searchable()
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
                                'identity' => str_replace('-', '', $newAccount->identity_document),
                            ]);
                        })
                        ->hidden(fn () => $this->otp !== null),

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
                                            $account->id => "{$account->bank_code->getLabel()} | {$account->phone_number} | {$account->identity_document}".
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
                                'identity' => str_replace('-', '', $bankAccount->identity_document),
                            ]);
                        })
                        ->hidden(fn () => $this->otp !== null),

                    // Botón para confirmar OTP
                    Action::make('confirmOtp')
                        ->label('Confirmar Clave de Seguridad (SMS)')
                        ->color('info')
                        ->form([
                            TextInput::make('otp')
                                ->label('Clave de Seguridad')
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
                        ->visible(fn () => $this->otp !== null),
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
        $phone = (string) str_replace('+58', '0', $this->phone);
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

        if (! $store) {
            throw new Exception('No se pudo obtener la tienda de la suscripción.');
        }

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
}
