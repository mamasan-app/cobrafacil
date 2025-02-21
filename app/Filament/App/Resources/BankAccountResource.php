<?php

namespace App\Filament\App\Resources;

use App\Enums\BankEnum;
use App\Filament\App\Resources\BankAccountResource\Pages;
use App\Filament\Inputs;
use App\Models\BankAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BankAccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Gestión de Pagos';

    protected static ?string $modelLabel = 'Cuenta Bancaria';

    protected static ?string $pluralModelLabel = 'Cuentas Bancarias';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
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

                        Forms\Components\Toggle::make('default_account')
                            ->columnSpanFull()
                            ->label('Predeterminada')
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('bank_code')
                    ->label('Banco'),

                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Número de teléfono'),

                Tables\Columns\TextColumn::make('identity_document')
                    ->label('Documento de identidad'),

                Tables\Columns\IconColumn::make('default_account')
                    ->label('Predeterminado')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y', 'America/Caracas'),
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
