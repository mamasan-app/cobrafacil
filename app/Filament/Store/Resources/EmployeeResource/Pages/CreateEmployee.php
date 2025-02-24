<?php

namespace App\Filament\Store\Resources\EmployeeResource\Pages;

use App\Filament\Store\Resources\EmployeeResource;
use App\Models\Store;
use App\Models\User;
use App\Notifications\WelcomeEmployeeNotification;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use MagicLink\Actions\LoginAction;
use MagicLink\MagicLink;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    public function create(bool $another = false): void
    {
        /** @var Store $store */
        $store = Filament::getTenant();

        $data = $this->form->getState();
        $data['email_verified_at'] = now();

        $user = User::where('email', $data['email'])->first();
        $this->email = $data['email'];

        if ($user) {
            $user->assignRole('employee');
            $user->stores()->attach($store->id, ['role' => 'employee']);
            $this->sendMagicLink($user);

            Notification::make()
                ->title('Empleado creado')
                ->body('El usuario fue asociado como empleado a la tienda y se le envió un enlace de inicio de sesión.')
                ->success()
                ->send();

            return;
        }

        parent::create();
    }

    protected function afterCreate(): void
    {
        /** @var User $this->record */
        $store = Filament::getTenant();
        $this->record->assignRole('employee');
        $this->record->stores()->attach($store->id, ['role' => 'employee']);
        $this->sendMagicLink($this->record);
    }

    protected function sendMagicLink(User $user): void
    {
        $route = route('filament.store.pages.dashboard');
        $action = new LoginAction($user);
        $action->response(fn () => redirect($route));
        $magicLinkUrl = MagicLink::create($action)->url;

        $store = Filament::getTenant();
        $storeName = $store ? $store->name : 'Nuestra Tienda';

        $user->notify(new WelcomeEmployeeNotification($magicLinkUrl, $storeName));
    }
}
