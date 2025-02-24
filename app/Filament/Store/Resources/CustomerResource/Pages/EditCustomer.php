<?php

namespace App\Filament\Store\Resources\CustomerResource\Pages;

use App\Filament\Store\Resources\CustomerResource;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->action(function () {
                    $store = Filament::getTenant();

                    DB::table('store_user')
                        ->where('store_id', $store->id)
                        ->where('user_id', $this->record->id)
                        ->where('role', 'customer')
                        ->delete();

                    redirect()->route('filament.store.resources.customers.index', [$store]);
                }),
        ];
    }
}
