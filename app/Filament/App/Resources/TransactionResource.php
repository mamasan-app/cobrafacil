<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use Filament\Forms\Form;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Gestión de Pagos';

    protected static ?string $modelLabel = 'Transacciones';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::query()->where('from_type', 'App\\Models\\User')
                    ->where('from_id', Auth::id()) // Filtrar por usuario en sesión
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
                    ->formatStateUsing(fn($state) => number_format($state, 2) . ' USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Detalles de la Transaccion')
                    ->tabs([
                        Tab::make('Informacion General')
                            ->schema([
                                TextEntry::make('id')
                                    ->label('ID de Transacción'),
                                TextEntry::make('type')
                                    ->label('Tipo de Transacción')
                                    ->getStateUsing(fn($record) => $record->type->getLabel()),
                                TextEntry::make('status')
                                    ->label('Estado')
                                    ->badge()
                                    ->color(fn($record) => $record->status->getColor()),
                                TextEntry::make('date')
                                    ->label('Fecha de Transacción')
                                    ->dateTime('d/m/Y'),
                                TextEntry::make('amount')
                                    ->label('Monto (USD)')
                                    ->getStateUsing(fn($record) => number_format($record->amount, 2) . ' USD'),
                                TextEntry::make('from_user_name')
                                    ->label('Usuario (From)')
                                    ->getStateUsing(fn($record) => $record->from->name ?? 'No disponible'),
                                TextEntry::make('to_name')
                                    ->label('Destino (To)')
                                    ->getStateUsing(function ($record) {
                                        if ($record->to_type === 'App\\Models\\Store') {
                                            return $record->to->name ?? 'No disponible';
                                        }
                                        if ($record->to_type === 'App\\Models\\User') {
                                            return $record->to->name ?? 'No disponible';
                                        }

                                        return 'No disponible';
                                    }),
                            ])->columns(2),

                        Tab::make('Información Detallada')
                            ->schema(function ($record) {
                                $metadata = $record->getMetadataAsObject();

                                if ($metadata instanceof \App\DTO\MiBancoMetadata) {
                                    return [
                                        TextEntry::make('mibanco_code')
                                            ->label('Código (MiBanco)')
                                            ->getStateUsing(fn() => $metadata->code)
                                            ->placeholder('No disponible'),

                                        TextEntry::make('mibanco_message')
                                            ->label('Mensaje (MiBanco)')
                                            ->getStateUsing(fn() => $metadata->message)
                                            ->placeholder('No disponible'),

                                        TextEntry::make('mibanco_reference')
                                            ->label('Referencia (MiBanco)')
                                            ->getStateUsing(fn() => $metadata->reference)
                                            ->placeholder('No disponible'),
                                        TextEntry::make('mibanco_id')
                                            ->label('ID (MiBanco)')
                                            ->getStateUsing(fn() => $metadata->id)
                                            ->placeholder('No disponible'),
                                    ];
                                }

                                return [
                                    TextEntry::make('metadata_details')
                                        ->label('Detalles de Metadata')
                                        ->placeholder('No disponible')
                                        ->getStateUsing(fn() => 'No se pudo determinar el tipo de metadata.'),
                                ];
                            })
                            ->columns(2),

                        Tab::make('Pago')
                            ->schema([
                                TextEntry::make('stripe_invoice_id')
                                    ->label('ID de Factura (Stripe)')
                                    ->placeholder('No disponible'),
                                TextEntry::make('amount_cents')
                                    ->label('Monto')
                                    ->getStateUsing(fn($record) => number_format($record->amount_cents / 100, 2) . ' USD')
                                    ->placeholder('No disponible'),
                                TextEntry::make('status')
                                    ->label('Estado')
                                    ->badge()
                                    ->color(fn($record) => $record->status->getColor())
                                    ->placeholder('No disponible'),
                                TextEntry::make('paid_date')
                                    ->label('Fecha de Pago')
                                    ->dateTime('d/m/Y')
                                    ->placeholder('No disponible'),
                            ])->columns(2),
                    ])->columnSpanFull(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'view' => Pages\ViewTransaction::route('/{record}'),
        ];
    }
}
