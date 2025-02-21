<?php

namespace App\Filament\App\Resources\SubscriptionResource\Widgets;

use App\Models\Payment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PaymentSubscriptionsWidget extends BaseWidget
{
    public $record;

    protected function getTableHeading(): ?string
    {
        return 'Pagos';
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
                    ->getStateUsing(fn ($record) => $record->status->getLabel())
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Completado' => 'success',
                        'Pendiente' => 'warning',
                        'Fallido', 'Cancelado' => 'danger',
                        'Incobrable' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_cents')
                    ->label('Monto (USD)')
                    ->getStateUsing(fn ($record) => number_format($record->amount_cents / 100, 2).' USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_date')
                    ->label('Fecha de Pago')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('No disponible'),
            ])
            ->filters([

            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->url(fn ($record) => route('filament.app.resources.payments.view', ['record' => $record->id]))
                    ->icon('heroicon-o-eye'),
            ]);
    }

    protected function getQuery()
    {
        // Recupera todos los pagos relacionados con la suscripción actual
        return Payment::query()
            ->where('subscription_id', $this->record->id);
    }
}
