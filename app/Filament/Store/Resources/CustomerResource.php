<?php

namespace App\Filament\Store\Resources;

use App\Filament\Inputs;
use App\Filament\Store\Resources\CustomerResource\Pages;
use App\Filament\Store\Resources\CustomerResource\RelationManagers\BankAccountsRelationManager;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class CustomerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $modelLabel = 'Cliente';

    protected static ?string $pluralModelLabel = 'Clientes';

    protected static ?string $navigationGroup = 'Usuarios';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Personal')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->required()
                            ->live(onBlur: false, debounce: 500)
                            ->afterStateHydrated(fn (Forms\Set $set) => $set('showAdditionalFields', true))
                            ->afterStateUpdated(function (?string $state, Pages\CreateCustomer|Pages\EditCustomer $livewire, Forms\Set $set) {
                                $userExists = User::where('email', $state)->exists();

                                if ($livewire instanceof Pages\CreateCustomer) {
                                    $createButton = $livewire->getAction('create');

                                    if ($userExists) {
                                        $createButton->label('Enviar enlace de inicio de sesión');
                                        $set('showAdditionalFields', false);
                                    } else {
                                        $createButton->label('Crear');
                                        $set('showAdditionalFields', true);
                                    }
                                }
                            })
                            ->helperText(function (Forms\Get $get) {
                                if (! $get('showAdditionalFields')) {
                                    return 'El usuario ya existe en el sistema. Puedes enviarle un enlace de inicio de sesión en el botón de abajo.';
                                }
                            }),

                        Forms\Components\TextInput::make('first_name')
                            ->label('Nombre')
                            ->required()
                            ->hidden(fn (Forms\Get $get) => ! $get('showAdditionalFields')),

                        Forms\Components\TextInput::make('last_name')
                            ->label('Apellido')
                            ->required()
                            ->hidden(fn (Forms\Get $get) => ! $get('showAdditionalFields')),

                        Inputs\PhoneNumberInput::make()
                            ->required()
                            ->hidden(fn (Forms\Get $get) => ! $get('showAdditionalFields'))
                            ->unique('users', 'phone_number'),

                        Inputs\IdentityPrefixSelect::make()
                            ->required()
                            ->hidden(fn (Forms\Get $get) => ! $get('showAdditionalFields')),

                        Inputs\IdentityNumberInput::make()
                            ->required()
                            ->hidden(fn (Forms\Get $get) => ! $get('showAdditionalFields'))
                            ->rules(function (Forms\Get $get) {
                                return [
                                    Rule::unique('users', 'identity_number')->where(function ($query) use ($get) {
                                        return $query->where('identity_prefix', $get('identity_prefix'));
                                    }),
                                ];
                            }),

                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Fecha de Nacimiento')
                            ->nullable()
                            ->hidden(fn (Forms\Get $get) => ! $get('showAdditionalFields')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('first_name')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('last_name')
                    ->label('Apellido')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo Electrónico')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Número de Teléfono')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('birth_date')
                    ->label('Fecha de Nacimiento')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última Actualización')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('stores', function ($query): void {
            $query->where('store_user.role', 'customer');
        });
    }

    public static function getRelations(): array
    {
        return [
            BankAccountsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
