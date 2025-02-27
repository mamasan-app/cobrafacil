<?php

namespace App\Filament\App\Resources\SubscriptionResource\Pages;

use App\Filament\App\Resources\SubscriptionResource;
use App\Filament\App\Resources\SubscriptionResource\Widgets\PaymentSubscriptionsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSubscription extends ViewRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function getFooterWidgets(): array
    {
        return [
            PaymentSubscriptionsWidget::class,
        ];
    }

    public function getTitle(): string
    {
        return 'Ver Suscripcion';
    }

    protected function getActions(): array
    {
        return [
            Actions\Action::make('Pagar')
                ->url(SubscriptionPayment::getUrl(['record' => $this->record]))
                ->color('success')
                ->icon('heroicon-o-currency-dollar')
                ->label('Pagar')
                ->button()
                ->visible(fn ($record) => $record->stripe_subscription_id === null),
        ];
    }
}
