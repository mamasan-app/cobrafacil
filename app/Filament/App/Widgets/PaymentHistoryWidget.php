<?php

declare(strict_types=1);

namespace App\Filament\App\Widgets;

use App\Models\Payment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PaymentHistoryWidget extends BaseWidget
{
    protected static ?string $heading = 'Historial de Pagos';

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->query(
                Payment::query()
                    ->whereHas('subscription', function ($query) {
                        $query->where('user_id', auth()->id());
                    })
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d M Y, h:i A', 'America/Caracas')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_cents')
                    ->label('Monto (USD)')
                    ->getStateUsing(fn ($record) => number_format($record->amount_cents / 100, 2).' USD'),
                Tables\Columns\TextColumn::make('subscription.service_name')
                    ->label('Servicio')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Completado' => 'success',
                        'Pendiente' => 'warning',
                        'Fallido', 'Cancelado' => 'danger',
                        'Incobrable' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
            ]);
    }
}
