<?php

namespace App\Filament\Store\Resources\BankAccountResource\Pages;

use App\Filament\Store\Resources\BankAccountResource;
use App\Models\BankAccount;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditBankAccount extends EditRecord
{
    protected static string $resource = BankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $store = Filament::getTenant();

        if ($this->record->default_account) {
            BankAccount::where('store_id', $store->id)
                ->where('id', '!=', $this->record->id)
                ->update(['default_account' => false]);
        }
    }
}
