<?php

declare(strict_types=1);

namespace App\Filament\Inputs;

use App\Enums\IdentityPrefixEnum;
use App\Filament\Fields\FilamentInput;
use Filament\Forms\Components\Select;

class IdentityPrefixSelect implements FilamentInput
{
    public static function make(string $name = 'identity_prefix'): Select
    {
        return Select::make($name)
            ->label('Tipo de Documento')
            ->options(
                collect(IdentityPrefixEnum::cases())
                    ->mapWithKeys(fn ($prefix) => [$prefix->value => $prefix->getLabel()])
                    ->toArray()
            )
            ->required();
    }
}
