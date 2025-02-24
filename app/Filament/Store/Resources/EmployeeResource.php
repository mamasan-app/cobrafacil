<?php

namespace App\Filament\Store\Resources;

use App\Filament\Inputs;
use App\Filament\Store\Resources\EmployeeResource\Pages;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class EmployeeResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $modelLabel = 'Empleados';

    protected static ?string $navigationGroup = 'Usuarios';

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->afterStateHydrated(fn (Forms\Set $set) => $set('showAdditionalFields', true))
                            ->live(onBlur: false, debounce: 500)
                            ->afterStateUpdated(function (?string $state, Pages\CreateEmployee|Pages\EditEmployee $livewire, Forms\Set $set) {
                                $userExists = User::where('email', $state)->exists();

                                if ($livewire instanceof Pages\CreateEmployee) {
                                    $createButton = $livewire->getAction('create');

                                    if ($userExists) {
                                        $createButton->label('Asociar');
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
                            ->maxLength(255),

                        Forms\Components\TextInput::make('last_name')
                            ->label('Apellido')
                            ->required()
                            ->maxLength(255),

                        Inputs\PhoneNumberInput::make()
                            ->required(),

                        Inputs\IdentityPrefixSelect::make()
                            ->required(),

                        Inputs\IdentityNumberInput::make()
                            ->required()
                            ->rules(function (Forms\Get $get, ?User $record) {
                                return [
                                    Rule::unique('users', 'identity_number')
                                        ->ignore($record?->id)
                                        ->where(function ($query) use ($get) {
                                            return $query->where('identity_prefix', $get('identity_prefix'));
                                        }),
                                ];
                            }),

                        Forms\Components\TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->required()
                            ->hiddenOn('edit')
                            ->confirmed()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Confirmar contraseña')
                            ->password()
                            ->revealable()
                            ->hiddenOn('edit')
                            ->autocomplete(false)
                            ->maxLength(255)
                            ->required(),

                        Forms\Components\TextInput::make('new_password')
                            ->label('Nueva contraseña')
                            ->nullable()
                            ->password()
                            ->hidden(fn () => ! auth()->user()->hasRole('owner_store'))
                            ->revealable()
                            ->visibleOn('edit')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('new_password_confirmation')
                            ->label('Confirmar contraseña')
                            ->password()
                            ->hidden(fn () => ! auth()->user()->hasRole('owner_store'))
                            ->revealable()
                            ->visibleOn('edit')
                            ->same('new_password')
                            ->requiredWith('new_password'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(static::getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable(query: function (Builder $query, string $search) {
                        $query
                            ->where('first_name', 'like', "{$search}%")
                            ->orWhere('last_name', 'like', "{$search}%");
                    }),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Número de teléfono')
                    ->searchable(),

                Tables\Columns\TextColumn::make('identity_document')
                    ->label('Identidificación')
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->whereRaw('CONCAT(identity_prefix,"-",identity_number) LIKE ?', ["{$search}%"]);
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('birth_date')
                    ->label('Fecha de nacimiento')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('address')
                    ->label('Dirección')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('Email verificado')
                    ->dateTime(null, 'America/Caracas')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? $state->format('d-m-Y H:i') : 'No verificado'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime(null, 'America/Caracas')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Fecha de Edición')
                    ->dateTime(null, 'America/Caracas')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Fecha de Eliminación')
                    ->dateTime(null, 'America/Caracas')
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getTableQuery()
    {
        // Obtener la tienda actual desde la sesión (usando Filament::getTenant())
        $currentStore = Filament::getTenant();

        if (! $currentStore) {
            // Si no hay tienda en sesión, devolver una consulta vacía
            return User::query()->whereRaw('1 = 0');
        }

        // Filtrar los usuarios con rol 'employee' asociados a la tienda actual
        return User::query()
            ->whereHas('stores', function ($query) use ($currentStore) {
                $query->where('stores.id', $currentStore->id);
            })
            ->role('employee');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployee::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        // Solo permitir a usuarios con el rol 'owner_store'
        return auth()->user()->hasRole('owner_store');
    }
}
