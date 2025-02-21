<?php

namespace App\Filament\Forms;

use App\Enums\BankEnum;
use App\Filament\Inputs;
use Filament\Forms;

class BankAccountForm
{
    public static function make(bool $hideDefaultAccount = false, string $bankCodeName = 'bank_code', string $phoneNumberName = 'phone_number', string $identityPrefixName = 'identity_prefix', string $identityNumberName = 'identity_number', string $defaultAccountName = 'default_account'): array
    {
        return [
            Forms\Components\Select::make($bankCodeName)
                ->label('Banco')
                ->options(BankEnum::class)
                ->searchable()
                ->required(),

            Inputs\PhoneNumberInput::make($phoneNumberName)
                ->label('Número de teléfono')
                ->required(),

            Inputs\IdentityPrefixSelect::make($identityPrefixName)
                ->required(),

            Inputs\IdentityNumberInput::make($identityNumberName)
                ->required(),

            Forms\Components\Toggle::make($defaultAccountName)
                ->columnSpanFull()
                ->hidden($hideDefaultAccount)
                ->label('Predeterminada')
                ->required(),
        ];
    }
}
