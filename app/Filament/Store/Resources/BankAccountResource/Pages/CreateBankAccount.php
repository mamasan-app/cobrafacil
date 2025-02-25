<?php

namespace App\Filament\Store\Resources\BankAccountResource\Pages;

use App\Filament\Store\Resources\BankAccountResource;
use App\Models\BankAccount;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateBankAccount extends CreateRecord
{
    protected static string $resource = BankAccountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }

    protected function afterCreate(): void
    {
        $store = Filament::getTenant();

        if ($this->record->default_account) {
            BankAccount::where('store_id', $store->id)
                ->where('id', '!=', $this->record->id)
                ->update(['default_account' => false]);
        }
    }
}
