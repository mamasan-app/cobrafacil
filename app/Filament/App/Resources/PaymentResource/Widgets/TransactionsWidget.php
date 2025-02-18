<?php

namespace App\Filament\App\Resources\PaymentResource\Widgets;

use App\Models\Transaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Enums\TransactionTypeEnum;

class TransactionsWidget extends BaseWidget
{
    public $record;

    protected function getTableHeading(): ?string
    {
        return 'Transacciones relacionadas';
    }

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->getQuery()
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo de Transacción')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn($record) => $record->status->getLabel())
                    ->badge()
                    ->color(fn($record) => $record->status->getColor())
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto (USD)')
                    ->formatStateUsing(fn($state) => number_format($state, 2) . 'USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->url(fn($record) => route('filament.app.resources.transactions.view', ['record' => $record->id]))
                    ->icon('heroicon-o-eye'),
            ]);
    }

    protected function getQuery()
    {
        // Recupera todas las transacciones relacionadas con el pago actual y que sean de tipo "subscription"
        return Transaction::query()
            ->where('payment_id', $this->record->id)
            ->where('type', TransactionTypeEnum::Subscription->value); // Filtrar solo suscripciones
    }
}
