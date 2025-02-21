<?php

namespace App\Filament\App\Resources\StoreResource\Widgets;

use App\Filament\App\Resources\SubscriptionResource\Pages\SubscriptionPayment;
use App\Models\Subscription;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class StoreSubscriptionsWidget extends BaseWidget
{
    public $record;

    protected function getTableHeading(): ?string
    {
        return 'Suscripciones de la Tienda';
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

                Tables\Columns\TextColumn::make('service_name')
                    ->label('Plan')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state?->getLabel()),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última Actualización')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                // Puedes añadir filtros aquí si es necesario
            ])
            ->actions([
                Action::make('Pagar')
                    ->url(fn (Subscription $record): string => SubscriptionPayment::getUrl(['record' => $record]))
                    ->color('success')
                    ->icon('heroicon-o-currency-dollar')
                    ->label('Pagar')
                    ->button()
                    ->visible(fn (Subscription $record) => $record->payments->flatMap->transactions->isEmpty()), // Mostrar solo si no hay transacciones
            ]);
    }

    protected function getQuery()
    {

        $storeId = $this->record->id;

        $userSubscriptions = Subscription::query()
            ->where('user_id', auth()->id())
            ->pluck('id');

        $storeSubscriptions = Subscription::query()
            ->where('store_id', $storeId)
            ->pluck('id');

        $intersectedSubscriptions = $userSubscriptions->intersect($storeSubscriptions);

        return Subscription::query()
            ->whereIn('id', $intersectedSubscriptions);
    }
}
