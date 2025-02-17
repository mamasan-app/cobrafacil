<?php

namespace App\Filament\Store\Resources\CustomerResource\RelationManagers;

use App\Enums\BankEnum;
use App\Filament\Inputs;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BankAccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'bankAccounts';

    public function form(Form $form): Form
    {
        return $form
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Cuentas Bancarias')
            ->modelLabel('Cuenta Bancaria')
            ->pluralModelLabel('Cuentas Bancarias')
            ->recordTitleAttribute('id')
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
                    ->dateTime('d/m/Y'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn () => $this->ownerRecord->bankAccounts()->count() === 0),
            ])
            ->actions([])
            ->bulkActions([]);
    }
}
