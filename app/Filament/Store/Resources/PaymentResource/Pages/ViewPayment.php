<?php

namespace App\Filament\Store\Resources\PaymentResource\Pages;

use App\Filament\Store\Resources\PaymentResource;
use App\Filament\Store\Resources\PaymentResource\Widgets\TransactionsWidget;
use App\Filament\Store\Resources\PaymentResource\Widgets\RefundTransactionsWidget;
use Filament\Resources\Pages\ViewRecord;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    /**
     * Muestra el widget de transacciones en el pie de la página.
     */
    protected function getFooterWidgets(): array
    {
        return [
            TransactionsWidget::class,
            RefundTransactionsWidget::class,
        ];
    }
}
