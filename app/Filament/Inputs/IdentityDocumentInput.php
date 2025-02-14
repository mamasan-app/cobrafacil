<?php

declare(strict_types=1);

namespace App\Filament\Inputs;

use App\Filament\Fields\FilamentInput;
use Filament\Forms\Components\TextInput;

class IdentityDocumentInput implements FilamentInput
{
    public static function make(string $name = 'identity_document'): TextInput
    {
        return TextInput::make($name)
            ->label('Documento de identidad')
            ->placeholder('12345678')
            ->hint('12345678');
    }
}
