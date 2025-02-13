<?php

namespace App\Filament\Store\Resources;

use App\Filament\Store\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use Filament\Facades\Filament;
use Filament\Forms\Form;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Gestión de Pagos';

    protected static ?string $modelLabel = 'Transacciones';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Esquema del formulario
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(static::getTableQuery())
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

                Tables\Columns\TextColumn::make('amount_cents')
                    ->label('Monto (USD)')
                    ->formatStateUsing(fn($record) => '$' . number_format($record->amount / 100, 2)),

                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                // Filtros personalizados
            ])
            ->actions([
                // Acciones personalizadas
            ])
            ->bulkActions([
                // Acciones masivas
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Detalles de la Transacción')
                    ->tabs([
                        Tab::make('Información General')
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
                                TextEntry::make('amount_cents')
                                    ->label('Monto (USD)')
                                    ->getStateUsing(fn($record) => number_format($record->amount_cents / 100, 2) . ' USD'),
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
                    ])->columnSpanFull(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Relaciones personalizadas
        ];
    }

    public static function getTableQuery(): Builder
    {
        $currentStore = Filament::getTenant();

        if (!$currentStore) {
            // Devuelve una consulta vacía si no hay tienda en sesión
            return Transaction::query()->whereRaw('1 = 0');
        }

        // Filtrar transacciones donde la tienda es el origen o el destino
        return Transaction::query()
            ->where(function (Builder $query) use ($currentStore) {
                $query->where(function ($subQuery) use ($currentStore) {
                    $subQuery->where('from_type', 'App\\Models\\Store')
                        ->where('from_id', $currentStore->id);
                })
                    ->orWhere(function ($subQuery) use ($currentStore) {
                        $subQuery->where('to_type', 'App\\Models\\Store')
                            ->where('to_id', $currentStore->id);
                    });
            });
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'view' => Pages\ViewTransaction::route('/{record}'),
        ];
    }
}
