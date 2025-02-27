<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\SubscriptionResource\Pages;
use App\Models\Plan;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $modelLabel = 'Suscripciones';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('store_id')
                ->label('Tienda')
                ->required()
                ->reactive()
                ->options(function () {
                    $currentUser = auth()->user();

                    return $currentUser->stores()->select('stores.name', 'stores.id')->pluck('name', 'id');
                })
                ->afterStateHydrated(function (callable $set, callable $get) {
                    $storeId = request()->query('store_id');
                    if ($storeId && ! $get('store_id')) {
                        $set('store_id', $storeId);
                    }
                })
                ->afterStateUpdated(fn (callable $set) => $set('service_id', null)),

            Forms\Components\Select::make('service_id')
                ->label('Servicio')
                ->required()
                ->options(function (callable $get) {
                    $storeId = $get('store_id');

                    return $storeId
                        ? Plan::where('store_id', $storeId)->pluck('name', 'id')
                        : [];
                }),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(static::getTableQuery())
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->placeholder('No disponible'),

                Tables\Columns\TextColumn::make('store.name')
                    ->label('Tienda')
                    ->sortable()
                    ->searchable()
                    ->placeholder('No disponible'),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Servicio')
                    ->sortable()
                    ->searchable()
                    ->placeholder('No disponible'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state?->getLabel())
                    ->color(fn ($record) => $record->status->getColor()),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Fecha de Expiración')
                    ->date('d/m/Y', 'America/Caracas')
                    ->placeholder('No disponible'),

                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label('Fin del Período de Prueba')
                    ->date('d/m/Y', 'America/Caracas')
                    ->placeholder('No disponible'),
            ])
            ->actions([
                Action::make('Pagar')
                    ->url(fn (Subscription $record): string => Pages\SubscriptionPayment::getUrl(['record' => $record]))
                    ->color('success')
                    ->icon('heroicon-o-currency-dollar')
                    ->label('Pagar')
                    ->button(),
            ]);

    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Detalles de la Suscripción')
                    ->tabs([
                        Tab::make('Suscripción')
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Estado')
                                    ->getStateUsing(fn ($record) => $record->status->getLabel())
                                    ->badge()
                                    ->color(fn ($record) => $record->status->getColor()),

                                TextEntry::make('trial_ends_at')
                                    ->label('Fin del Periodo de Prueba')
                                    ->date('d/m/Y', 'America/Caracas')
                                    ->placeholder('No disponible'),

                                TextEntry::make('renews_at')
                                    ->label('Renovación')
                                    ->date('d/m/Y', 'America/Caracas')
                                    ->placeholder('No disponible'),

                                TextEntry::make('ends_at')
                                    ->label('Fecha de Finalización')
                                    ->date('d/m/Y', 'America/Caracas')
                                    ->placeholder('No disponible'),

                                TextEntry::make('last_notification_at')
                                    ->label('Última Notificación')
                                    ->date('d/m/Y', 'America/Caracas')
                                    ->placeholder('No disponible'),

                                TextEntry::make('expires_at')
                                    ->label('Fecha de Expiración')
                                    ->date('d/m/Y', 'America/Caracas')
                                    ->placeholder('No disponible'),

                                TextEntry::make('frequency_days')
                                    ->label('Frecuencia de Pago (días)')
                                    ->placeholder('No disponible'),
                            ])->columns(2),

                        Tab::make('Plan')
                            ->schema([
                                TextEntry::make('service_name')
                                    ->label('Nombre del Servicio')
                                    ->placeholder('No disponible'),

                                TextEntry::make('service_description')
                                    ->label('Descripción del Servicio')
                                    ->placeholder('No disponible'),

                                TextEntry::make('formattedServicePrice')
                                    ->label('Precio del Servicio')
                                    ->placeholder('No disponible'),

                                TextEntry::make('service_free_days')
                                    ->label('Días Gratis')
                                    ->placeholder('No disponible'),
                            ])->columns(2),

                        Tab::make('Tienda')
                            ->schema([
                                Group::make()
                                    ->schema([
                                        ImageEntry::make('store.logoUrl')
                                            ->label('Logo de la Tienda')
                                            ->circular()
                                            ->disk(config('filesystems.stores'))
                                            ->visibility('private')
                                            ->placeholder('No disponible')
                                            ->columnSpan(2),

                                        TextEntry::make('store.name')
                                            ->label('Nombre de la Tienda')
                                            ->placeholder('No disponible'),

                                        TextEntry::make('store.verified')
                                            ->label('Verificada')
                                            ->getStateUsing(fn ($record) => $record->store?->verified ? 'Sí' : 'No')
                                            ->badge()
                                            ->color(fn ($state) => $state === 'Sí' ? 'success' : 'danger'),
                                    ])
                                    ->columns(2),
                            ]),

                    ])->columnSpanFull(),
            ]);
    }

    public static function getTableQuery()
    {
        $currentUser = auth()->user();

        return Subscription::query()
            ->where('user_id', $currentUser->id)
            ->with(['store.owner']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'view' => Pages\ViewSubscription::route('/{record}'),
            'payment' => Pages\SubscriptionPayment::route('/{record}/payment'),
        ];
    }
}
