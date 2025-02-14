<?php

declare(strict_types=1);

namespace App\Filament\Inputs;

use App\Filament\Fields\FilamentInput;
use Filament\Forms\Components\TextInput;
use Filament\Support\RawJs;

class PhoneNumberInput implements FilamentInput
{
    public static function make(string $name = 'phone_number'): TextInput
    {
        return TextInput::make($name)
            ->label('Número de teléfono')
            ->hint('+584241234567')
            ->placeholder('+584241234567')
            ->default('+58')
            ->mask(RawJs::make(<<<'JS'
                '+589999999999'
            JS))
            ->regex('/^\+58\d{10}$/');
    }
}
