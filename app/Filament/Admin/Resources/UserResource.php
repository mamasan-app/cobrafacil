<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Filament\Inputs;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rule;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $modelLabel = 'Usuarios';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('first_name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('last_name')
                    ->label('Apellido')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),

                Inputs\PhoneNumberInput::make()
                    ->required(),

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

                Forms\Components\DatePicker::make('birth_date')
                    ->label('Fecha de nacimiento'),

                Forms\Components\TextInput::make('address')
                    ->label('Dirección')
                    ->maxLength(255),

                Forms\Components\FileUpload::make('selfie_path')
                    ->label('Selfie (Imágen)')
                    ->disk(config('filesystems.users'))
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('ci_picture_path')
                    ->label('Documento de Identidad')
                    ->disk(config('filesystems.users'))
                    ->maxFiles(1)
                    ->columnSpanFull(),

                Forms\Components\Select::make('role')
                    ->label('Role')
                    ->options([
                        'admin' => 'Admin',
                        'owner_store' => 'Owner',
                    ])
                    ->required()
                    ->default('owner_store'),

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
                    ->revealable()
                    ->visibleOn('edit')
                    ->maxLength(255),

                Forms\Components\TextInput::make('new_password_confirmation')
                    ->label('Confirmar contraseña')
                    ->password()
                    ->revealable()
                    ->visibleOn('edit')
                    ->same('new_password')
                    ->requiredWith('new_password'),
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
                    ->dateTime()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? $state->format('d-m-Y H:i') : 'No verificado'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
