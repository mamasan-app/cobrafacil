<?php

namespace App\Filament\Store\Resources\EmployeeResource\Pages;

use App\Filament\Store\Resources\EmployeeResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function afterCreate(): void
    {
        $store = Filament::getTenant();
        $this->record->assignRole('employee');
        $this->record->stores()->attach($store->id, ['role' => 'employee']);
    }
}
