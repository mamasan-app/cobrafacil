<?php

namespace App\Filament\Pages\Auth;

use App\Filament\Forms\BankAccountForm;
use App\Filament\Inputs;
use App\Models\Address;
use App\Models\BankAccount;
use App\Models\Store;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Register as FilamentRegister;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

    protected function generalInformationStep(): Wizard\Step
    {
        return Wizard\Step::make('Información General')
            ->columns(2)
            ->schema([
                TextInput::make('first_name')
                    ->required()
                    ->label('Nombre')
                    ->placeholder('John'),

                TextInput::make('last_name')
                    ->required()
                    ->label('Apellido')
                    ->placeholder('Doe'),

                TextInput::make('email')
                    ->required()
                    ->email()
                    ->unique('users', 'email')
                    ->label('Correo Electrónico')
                    ->placeholder('johndoe@example.com'),

                Inputs\PhoneNumberInput::make()
                    ->unique('users', 'phone_number')
                    ->required(),

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
            ]);
    }

    protected function legalInformationStep(): Wizard\Step
    {
        return Wizard\Step::make('Información Representante Legal')
            ->columns(2)
            ->schema([
                Inputs\IdentityPrefixSelect::make()
                    ->required(),

                Inputs\IdentityNumberInput::make()
                    ->required()
                    ->rules(function (Forms\Get $get) {
                        return [
                            Rule::unique('users', 'identity_number')->where(function ($query) use ($get) {
                                return $query->where('identity_prefix', $get('identity_prefix'));
                            }),
                        ];
                    }),

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
            ]);
    }

    protected function storeInformationStep(): Wizard\Step
    {
        return Wizard\Step::make('Información de la Tienda')
            ->columns(2)
            ->schema([
                TextInput::make('store_name')
                    ->label('Nombre de la Tienda')
                    ->unique('stores', 'name')
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

                Forms\Components\Section::make('Información de la Cuenta Bancaria')
                    ->description('Por favor, proporciona los datos de la cuenta bancaria de la tienda.')
                    ->schema(BankAccountForm::make(
                        true,
                        'store_bank_code',
                        'store_phone_number',
                        'store_identity_prefix',
                        'store_identity_number',
                    ))
                    ->columns(2),
            ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    $this->generalInformationStep(),
                    $this->legalInformationStep(),
                    $this->storeInformationStep(),
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

    protected function handleRegistration(array $data): Model
    {
        $data = $this->form->getState();

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'password' => $data['password'],
            'identity_prefix' => $data['identity_prefix'],
            'identity_number' => $data['identity_number'],
            'birth_date' => $data['birth_date'],
            'address' => $data['address'],
            'selfie_path' => $data['selfie_path'],
            'ci_picture_path' => $data['ci_picture_path'],
        ]);

        $user->assignRole('owner_store');

        $store = Store::create([
            'name' => $data['store_name'],
            'slug' => Str::slug($data['store_name']),
            'description' => $data['store_description'],
            'rif_path' => $data['store_rif_path'],
            'constitutive_document_path' => $data['constitutive_document_path'],
            'owner_id' => $user->id,
        ]);

        Address::create([
            'branch' => $data['short_address'],
            'location' => $data['long_address'],
            'store_id' => $store->id,
        ]);

        BankAccount::create([
            'store_id' => $store->id,
            'bank_code' => $data['store_bank_code'],
            'phone_number' => $data['store_phone_number'],
            'identity_number' => $data['store_identity_number'],
            'identity_prefix' => $data['store_identity_prefix'],
            'default_account' => true,
            'user_id' => $user->id,
        ]);

        $user->stores()->attach($store->id, ['role' => 'owner_store']);

        Notification::make()
            ->title('Registro exitoso')
            ->body('Bienvenido a CobraFácil! Tu cuenta ha sido creada exitosamente.')
            ->success()
            ->send();

        return $user;
    }
}
