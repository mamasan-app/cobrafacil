<?php

namespace App\Filament\App\Resources;

use App\Enums\PaymentStatusEnum;
use App\Filament\App\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms\Form;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Gestión de Pagos';

    protected static ?string $modelLabel = 'Pagos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(static::getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('subscription.service_name')
                    ->label('Servicio')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount_cents')
                    ->label('Monto (USD)')
                    ->getStateUsing(fn ($record) => number_format($record->amount_cents / 100, 2).' USD')
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

                Tables\Columns\TextColumn::make('paid_date')
                    ->label('Fecha de Pago')
                    ->dateTime('d/m/Y', 'America/Caracas')
                    ->sortable()
                    ->placeholder('No disponible'),
            ])
            ->filters([
                Tables\Filters\Filter::make('Estado: Completado')
                    ->query(fn ($query) => $query->where('status', PaymentStatusEnum::Completed->value)),

                Tables\Filters\Filter::make('Estado: Pendiente')
                    ->query(fn ($query) => $query->where('status', PaymentStatusEnum::Pending->value)),

                Tables\Filters\Filter::make('Vencidos')
                    ->query(fn ($query) => $query->where('due_date', '<', now())->whereNull('paid_date')),
            ])
            ->actions([

            ])
            ->bulkActions([

            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Detalles del Pago')
                    ->tabs([
                        // Pestaña Información del Pago
                        Tab::make('Pago')
                            ->schema([
                                TextEntry::make('stripe_invoice_id')
                                    ->label('ID de Factura (Stripe)')
                                    ->placeholder('No disponible'),
                                TextEntry::make('amount_cents')
                                    ->label('Monto')
                                    ->getStateUsing(fn ($record) => number_format($record->amount_cents / 100, 2).' USD')
                                    ->placeholder('No disponible'),
                                TextEntry::make('status')
                                    ->label('Estado')
                                    ->badge()
                                    ->color(fn ($record) => $record->status->getColor())
                                    ->placeholder('No disponible'),
                                TextEntry::make('paid_date')
                                    ->label('Fecha de Pago')
                                    ->dateTime('d/m/Y', 'America/Caracas')
                                    ->placeholder('No disponible'),
                            ])->columns(2),

                        // Pestaña Información de la Suscripción
                        Tab::make('Suscripción')
                            ->schema([
                                TextEntry::make('subscription.status')
                                    ->label('Estado')
                                    ->getStateUsing(fn ($record) => $record->status->getLabel())
                                    ->badge()
                                    ->color(fn ($record) => $record->status->getColor()),
                                TextEntry::make('subscription.trial_ends_at')
                                    ->label('Fin del Periodo de Prueba')
                                    ->date('d/m/Y')
                                    ->placeholder('No disponible'),
                                TextEntry::make('subscription.renews_at')
                                    ->label('Renovación')
                                    ->date('d/m/Y')
                                    ->placeholder('No disponible'),
                                TextEntry::make('subscription.ends_at')
                                    ->label('Fecha de Finalización')
                                    ->date('d/m/Y')
                                    ->placeholder('No disponible'),
                                TextEntry::make('subscription.last_notification_at')
                                    ->label('Última Notificación')
                                    ->date('d/m/Y')
                                    ->placeholder('No disponible'),
                                TextEntry::make('subscription.expires_at')
                                    ->label('Fecha de Expiración')
                                    ->date('d/m/Y')
                                    ->placeholder('No disponible'),
                                TextEntry::make('subscription.frequency_days')
                                    ->label('Frecuencia de Pago (días)')
                                    ->placeholder('No disponible'),
                            ])->columns(2),
                        // Pestaña Información del Plan
                        Tab::make('Plan')
                            ->schema([
                                TextEntry::make('subscription.service_name')
                                    ->label('Nombre del Servicio')
                                    ->placeholder('No disponible'),
                                TextEntry::make('subscription.service_description')
                                    ->label('Descripción del Servicio')
                                    ->placeholder('No disponible'),
                                TextEntry::make('subscription.formattedServicePrice')
                                    ->label('Precio del Servicio')
                                    ->placeholder('No disponible'),
                                TextEntry::make('subscription.service_free_days')
                                    ->label('Días Gratis')
                                    ->placeholder('No disponible'),
                                TextEntry::make('subscription.service_grace_period')
                                    ->label('Período de Gracia')
                                    ->placeholder('No disponible'),
                            ])->columns(2),
                    ])->columnSpanFull(),
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
        // Retorna la consulta con los pagos asociados al usuario autenticado
        return parent::getEloquentQuery()
            ->whereHas('subscription', function ($query) {
                $query->where('user_id', auth()->id());
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'view' => Pages\ViewPayment::route('/{record}'),
        ];
    }
}
