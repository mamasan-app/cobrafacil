<?php

namespace App\Filament\Store\Resources\PaymentResource\Widgets;

use App\Models\Transaction;
use Filament\Facades\Filament;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RefundTransactionsWidget extends BaseWidget
{
    public $record; // Asegura que el widget tiene acceso al pago actual

    protected function getTableHeading(): ?string
    {
        return 'Transacciones de Vuelto';
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

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn($record) => $record->status->getLabel())
                    ->badge()
                    ->color(fn($record) => $record->status->getColor())
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_cents')
                    ->label('Monto (USD)')
                    ->formatStateUsing(fn($record) => '$' . number_format($record->amount_cents / 100, 2)),

                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->url(fn($record) => route('filament.store.resources.transactions.view', [
                        'tenant' => Filament::getTenant()->slug, // Obtener el tenant actual
                        'record' => $record->id,
                    ]))
                    ->icon('heroicon-o-eye'),
            ]);
    }

    protected function getQuery()
    {
        // Obtener el store_id del tenant actual
        $storeId = Filament::getTenant()->id;

        // Asegurar que el widget tiene acceso al pago actual
        if (!$this->record) {
            return Transaction::query()->whereRaw('1 = 0'); // Retorna una consulta vacía si no hay un pago asociado
        }

        return Transaction::query()
            ->where('to_type', 'App\\Models\\Store')
            ->where('to_id', $storeId)
            ->where('type', 'refund')
            ->where('payment_id', $this->record->id); // Filtrar por el pago actual
    }
}
