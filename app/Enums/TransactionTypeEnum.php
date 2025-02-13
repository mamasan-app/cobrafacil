<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TransactionTypeEnum: string implements HasLabel
{
    case Subscription = 'subscription';
    case Refund = 'refund';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Subscription => 'Subscripción',
            self::Refund => 'Pagado',
        };
    }
}
