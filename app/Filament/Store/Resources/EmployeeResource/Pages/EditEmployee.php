<?php

namespace App\Filament\Store\Resources\EmployeeResource\Pages;

use App\Filament\Store\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->action(function () {
                    $store = Filament::getTenant();

                    DB::table('store_user')
                        ->where('store_id', $store->id)
                        ->where('user_id', $this->record->id)
                        ->where('role', 'employee')
                        ->delete();

                    redirect()->route('filament.store.resources.employees.index', [$store]);
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $password = $data['new_password'] ?? null;

        if ($password) {
            $data['password'] = bcrypt($password);
        }

        return $data;
    }
}
