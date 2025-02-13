<?php

namespace App\Filament\App\Resources;

use App\Enums\BankEnum;
use App\Enums\PhonePrefixEnum;
use App\Filament\App\Resources\BankAccountResource\Pages;
use App\Models\BankAccount;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BankAccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Gestión de Pagos';

    protected static ?string $modelLabel = 'Cuentas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('bank_code')
                    ->label('Banco')
                    ->options(
                        collect(BankEnum::cases())->mapWithKeys(fn ($bank) => [$bank->code() => $bank->getLabel()])->toArray()
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
                        TextInput::make('phone_number')
                            ->label('Número Telefónico')
                            ->numeric()
                            ->minLength(7)
                            ->maxLength(7)
                            ->required(),
                    ]),
                Forms\Components\Toggle::make('default_account')
                    ->label('Predeterminada')
                    ->required(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('bank_code')
                    ->label('Banco')
                    ->formatStateUsing(function ($state) {
                        $bank = collect(BankEnum::cases())
                            ->first(fn ($bank) => $bank->code() === $state);

                        return $bank?->getLabel() ?? 'Desconocido';
                    }),
                TextColumn::make('phone_number')
                    ->label('Número de teléfono'),
                TextColumn::make('identity_number')
                    ->label('Número de identidad'),
                Tables\Columns\IconColumn::make('default_account')
                    ->label('Publicado')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y'),
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
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id());
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBankAccounts::route('/'),
            'create' => Pages\CreateBankAccount::route('/create'),
            'edit' => Pages\EditBankAccount::route('/{record}/edit'),
        ];
    }
}
