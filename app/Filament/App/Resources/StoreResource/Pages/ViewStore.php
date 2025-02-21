<?php

namespace App\Filament\App\Resources\StoreResource\Pages;

use App\Filament\App\Resources\StoreResource;
use App\Filament\App\Resources\StoreResource\Widgets\StorePlansWidget;
use App\Filament\App\Resources\StoreResource\Widgets\StoreSubscriptionsWidget;
use App\Filament\App\Resources\SubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewStore extends ViewRecord
{
    protected static string $resource = StoreResource::class;

    /**
     * Muestra el widget de suscripciones en el pie de la página.
     */
    protected function getFooterWidgets(): array
    {
        return [
            StorePlansWidget::class,
            StoreSubscriptionsWidget::class,
        ];
    }

    /**
     * Configura las acciones disponibles en la vista.
     */
    protected function getActions(): array
    {
        return [
            Actions\Action::make('Crear Suscripción')
                ->url(fn () => SubscriptionResource::getUrl('create', [
                    'store_id' => $this->record->id,
                ]))
                ->color('primary')
                ->icon('heroicon-o-plus')
                ->label('Crear Suscripción'),
        ];
    }
}
