<?php

namespace App\Filament\Store\Resources\PaymentResource\Widgets;

use App\Models\Transaction;
use Filament\Facades\Filament;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

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
                    ->formatStateUsing(fn ($record) => $record->status->getLabel())
                    ->badge()
                    ->color(fn ($record) => $record->status->getColor())
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_cents')
                    ->label('Monto (USD)')
                    ->formatStateUsing(fn ($record) => '$'.number_format($record->amount / 100, 2)),

                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->url(fn ($record) => route('filament.store.resources.transactions.view', [
                        'tenant' => Filament::getTenant()->slug, // Obtener el tenant actual
                        'record' => $record->id,
                    ]))
                    ->icon('heroicon-o-eye'),
            ]);
    }

    protected function getQuery()
    {
        // Recupera todas las transacciones relacionadas con el pago actual y que sean de tipo "subscription"
        return Transaction::query()
            ->where('payment_id', $this->record->id)
            ->where('type', 'subscription'); // Agregar filtro por tipo de transacción
    }
}
