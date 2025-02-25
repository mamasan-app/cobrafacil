<?php

namespace App\Filament\App\Resources\BankAccountResource\Pages;

use App\Filament\App\Resources\BankAccountResource;
use App\Models\BankAccount;
use Filament\Resources\Pages\CreateRecord;

class CreateBankAccount extends CreateRecord
{
    protected static string $resource = BankAccountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $user = auth()->user();

        if ($this->record->default_account) {
            BankAccount::where('user_id', $user->id)
                ->where('id', '!=', $this->record->id)
                ->update(['default_account' => false]);
        }
    }
}
