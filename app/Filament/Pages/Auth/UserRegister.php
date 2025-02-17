<?php

namespace App\Filament\Pages\Auth;

use App\Enums\BankEnum;
use App\Enums\PhonePrefixEnum;
use App\Filament\Inputs;
use App\Models\Store;
use App\Models\User;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Register as FilamentRegister;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class UserRegister extends FilamentRegister
{
    protected static string $view = 'filament.pages.auth.register';

    protected ?string $maxWidth = MaxWidth::FourExtraLarge->value;

    public function mount(): void
    {
        parent::mount();

        if (config('app.env') !== 'local') {
            return;
        }

        $this->form->fill(array_merge(
            User::factory()->make()->toArray(),
            [
                'password' => 'password',
                'password_confirmation' => 'password',
            ]
        ));
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    // Paso 1: Información General del Usuario
                    Wizard\Step::make('Información General')
                        ->columns(2)
                        ->schema([
                            TextInput::make('first_name')
                                ->required()
                                ->label('Nombre')
                                ->placeholder('Carlos'),

                            TextInput::make('last_name')
                                ->required()
                                ->label('Apellido')
                                ->placeholder('Pérez'),

                            TextInput::make('email')
                                ->required()
                                ->email()
                                ->label('Correo Electrónico')
                                ->placeholder('carlosperez@example.com'),

                            TextInput::make('phone_number')
                                ->required()
                                ->label('Número de Teléfono')
                                ->placeholder('04241234567'),

                            TextInput::make('password')
                                ->required()
                                ->password()
                                ->label('Contraseña')
                                ->placeholder('********')
                                ->rule(Password::default())
                                ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                                ->same('password_confirmation')
                                ->validationAttribute(__('filament-panels::pages/auth/register.form.password.validation_attribute')),

                            TextInput::make('password_confirmation')
                                ->required()
                                ->password()
                                ->label('Confirmar Contraseña')
                                ->placeholder('********')
                                ->dehydrated(false),
                        ]),

                    // Paso 2: Información Personal del Usuario
                    Wizard\Step::make('Información Representante Legal')
                        ->columns(2)
                        ->schema([
                            Inputs\IdentityPrefixSelect::make()
                                ->required(),

                            Inputs\IdentityNumberInput::make()
                                ->required(),

                            DatePicker::make('birth_date')
                                ->label('Fecha de Nacimiento')
                                ->placeholder('01/01/2000'),

                            Textarea::make('address')
                                ->required()
                                ->columnSpanFull()
                                ->label('Dirección')
                                ->placeholder('Av. 1 con Calle 1, Edificio 1, Piso 1, Apartamento 1'),

                            FileUpload::make('selfie_path')
                                ->label('Selfie')
                                ->image()
                                ->disk(config('filesystems.users'))
                                ->maxFiles(1)
                                ->placeholder('selfie.jpg'),

                            FileUpload::make('ci_picture_path')
                                ->label('Foto de la Cédula')
                                ->image()
                                ->disk(config('filesystems.users'))
                                ->maxFiles(1)
                                ->placeholder('ci.jpg'),

                            Checkbox::make('terms_and_conditions_accepted')
                                ->columnSpanFull()
                                ->accepted()
                                ->label('Acepto los términos y condiciones'),
                        ]),

                    // Paso 3: Información de la Tienda
                    Wizard\Step::make('Información de la Tienda')
                        ->columns(2)
                        ->schema([
                            // Información general de la tienda
                            TextInput::make('store_name')
                                ->label('Nombre de la Tienda')
                                ->required(),

                            Textarea::make('store_description')
                                ->label('Descripción de la Tienda')
                                ->required()
                                ->placeholder('Descripción breve de la tienda'),

                            TextInput::make('short_address')
                                ->label('Sucursal')
                                ->placeholder('Altamira')
                                ->required(),

                            Textarea::make('long_address')
                                ->label('Dirección del Negocio')
                                ->required()
                                ->placeholder('Av. 1 con Calle 1, Edificio 1, Piso 1, Apartamento 1'),

                            FileUpload::make('store_rif_path')
                                ->label('RIF de la Tienda')
                                ->disk(config('filesystems.stores'))
                                ->maxFiles(1)
                                ->placeholder('rif.jpg'),

                            FileUpload::make('constitutive_document_path')
                                ->label('Documento Constitutivo')
                                ->disk(config('filesystems.stores'))
                                ->maxFiles(1)
                                ->placeholder('certificate.jpg'),

                            // Sección para la cuenta bancaria
                            \Filament\Forms\Components\Section::make('Información de la Cuenta Bancaria')
                                ->description('Por favor, proporciona los datos de la cuenta bancaria de la tienda.')
                                ->schema([
                                    Select::make('bank_code')
                                        ->label('Banco')
                                        ->options(
                                            collect(BankEnum::cases())
                                                ->mapWithKeys(fn ($bank) => [$bank->code() => $bank->getLabel()])
                                                ->toArray()
                                        )
                                        ->required(),

                                    Grid::make(2)
                                        ->schema([
                                            Select::make('phone_prefix')
                                                ->label('Prefijo Telefónico')
                                                ->options(
                                                    collect(PhonePrefixEnum::cases())
                                                        ->mapWithKeys(fn ($prefix) => [$prefix->value => $prefix->getLabel()])
                                                        ->toArray()
                                                )
                                                ->required(),

                                            TextInput::make('bank_phone_number')
                                                ->label('Número Telefónico')
                                                ->numeric()
                                                ->minLength(7)
                                                ->maxLength(7)
                                                ->required(),
                                        ]),

                                    TextInput::make('store_identity_number')
                                        ->label('Número de Identidad')
                                        ->required()
                                        ->placeholder('V-12345678'),
                                ])
                                ->columns(2), // Organiza los campos en dos columnas dentro de la sección
                        ]),
                ])->submitAction(new HtmlString(Blade::render(<<<'BLADE'
                    <x-filament::button
                        type="submit"
                        size="sm"
                    >
                        Registrarse
                    </x-filament::button>
                BLADE))),
            ]);
    }

    protected function validateAndNotify(array $data): bool
    {
        $errors = false;

        // Verificar si el email ya existe
        if (User::where('email', $data['email'])->exists()) {
            Notification::make()
                ->title('Error de validación')
                ->body('El correo electrónico ya está registrado.')
                ->danger()
                ->send();
            $errors = true;
        }

        // Verificar si el número de teléfono ya existe
        if (! empty($data['phone_number']) && User::where('phone_number', $data['phone_number'])->exists()) {
            Notification::make()
                ->title('Error de validación')
                ->body('El número de teléfono ya está registrado.')
                ->danger()
                ->send();
            $errors = true;
        }

        // Verificar si el documento de identidad ya existe
        if (! empty($data['identity_prefix']) && ! empty($data['identity_number'])) {
            $identityDocument = $data['identity_prefix'].'-'.$data['identity_number'];
            if (User::where('identity_document', $identityDocument)->exists()) {
                Notification::make()
                    ->title('Error de validación')
                    ->body('El documento de identidad ya está registrado.')
                    ->danger()
                    ->send();
                $errors = true;
            }
        }

        // Validar si se aceptaron los términos y condiciones
        if (empty($data['terms_and_conditions_accepted'])) {
            Notification::make()
                ->title('Error de validación')
                ->body('Debe aceptar los términos y condiciones.')
                ->danger()
                ->send();
            $errors = true;
        }

        return ! $errors; // Retorna `true` si no hubo errores, `false` si los hubo
    }

    protected function validateStoreAndNotify(array $data): bool
    {
        $errors = false;

        // Validar si el nombre de la tienda ya existe
        if (Store::where('name', $data['store_name'])->exists()) {
            Notification::make()
                ->title('Error de validación')
                ->body('El nombre de la tienda ya está registrado.')
                ->danger()
                ->send();
            $errors = true;
        }

        // Validar si el slug ya existe (se genera automáticamente si no se proporciona)
        $slug = $data['store_slug'] ?? Str::slug($data['store_name']);
        if (Store::where('slug', $slug)->exists()) {
            Notification::make()
                ->title('Error de validación')
                ->body('El identificador único (slug) de la tienda ya está registrado.')
                ->danger()
                ->send();
            $errors = true;
        }

        return ! $errors; // Retorna `true` si no hubo errores, `false` si los hubo
    }

    public function register(): ?RegistrationResponse
    {
        $data = $this->form->getState();

        // Validar usuario y tienda con notificaciones en caso de errores
        if (! $this->validateAndNotify($data) || ! $this->validateStoreAndNotify($data)) {
            return null; // Detener el flujo si hay errores
        }

        try {
            // Iniciar una transacción
            \DB::beginTransaction();

            // Crear el usuario
            $data['identity_document'] = $data['identity_prefix'].'-'.$data['identity_number'];
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone_number' => $data['phone_number'],
                'password' => $data['password'],
                'identity_document' => $data['identity_document'],
                'birth_date' => $data['birth_date'],
                'address' => $data['address'],
                'selfie_path' => $data['selfie_path'],
                'ci_picture_path' => $data['ci_picture_path'],
            ]);

            // Asignar rol al usuario
            $user->assignRole('owner_store');

            // Crear la tienda asociada al usuario
            $store = Store::create([
                'name' => $data['store_name'],
                'slug' => $data['store_slug'] ?? Str::slug($data['store_name']),
                'description' => $data['store_description'],
                'rif_path' => $data['store_rif_path'],
                'constitutive_document_path' => $data['constitutive_document_path'],
                'owner_id' => $user->id,
            ]);

            // Crear dirección de la tienda
            \App\Models\Address::create([
                'branch' => $data['short_address'],
                'location' => $data['long_address'],
                'store_id' => $store->id,
            ]);

            // Crear la cuenta bancaria de la tienda
            \App\Models\BankAccount::create([
                'store_id' => $store->id,
                'bank_code' => $data['bank_code'],
                'phone_number' => $data['phone_prefix'].$data['bank_phone_number'],
                'identity_number' => $data['store_identity_number'],
                'default_account' => true, // Marcar como cuenta predeterminada
                'user_id' => $user->id, // Agregar el ID del usuario
            ]);

            // Asociar la tienda al usuario
            $user->stores()->attach($store->id, ['role' => 'owner_store']);

            // Confirmar la transacción
            \DB::commit();

            Notification::make()
                ->title('Registro exitoso')
                ->body('El usuario y la tienda se registraron correctamente.')
                ->success()
                ->send();

            return $this->registered($user);
        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            \DB::rollBack();

            Notification::make()
                ->title('Error crítico')
                ->body('Ocurrió un error inesperado: '.$e->getMessage())
                ->danger()
                ->send();

            return null;
        }
    }

    protected function registered(User $user): ?RegistrationResponse
    {
        return app(RegistrationResponse::class);
    }
}
