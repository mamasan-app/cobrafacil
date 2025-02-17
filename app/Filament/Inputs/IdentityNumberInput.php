<?php

declare(strict_types=1);

namespace App\Filament\Inputs;

use App\Filament\Fields\FilamentInput;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Illuminate\Validation\Rule;

class IdentityNumberInput implements FilamentInput
{
    public static function make(string $name = 'identity_number'): TextInput
    {
        return TextInput::make($name)
            ->label('Documento de identidad')
            ->placeholder('12345678')
            ->hint('12345678')
            ->rules(function (Forms\Get $get) {
                return [
                    Rule::unique('users', 'identity_number')->where(function ($query) use ($get) {
                        return $query->where('identity_prefix', $get('identity_prefix'));
                    }),
                ];
            });
    }
}
