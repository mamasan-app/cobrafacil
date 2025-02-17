<?php

declare(strict_types=1);

namespace App\Filament\Inputs;

use App\Filament\Fields\FilamentInput;
use Filament\Forms\Components\TextInput;

class IdentityNumberInput implements FilamentInput
{
    public static function make(string $name = 'identity_number'): TextInput
    {
        return TextInput::make($name)
            ->label('Documento de identidad')
            ->placeholder('12345678')
            ->hint('12345678');
    }
}
