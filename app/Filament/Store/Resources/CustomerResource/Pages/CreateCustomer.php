<?php

namespace App\Filament\Store\Resources\CustomerResource\Pages;

use App\Filament\Store\Resources\CustomerResource;
use App\Models\Store;
use App\Models\User;
use App\Notifications\WelcomeCustomerNotification;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use MagicLink\Actions\LoginAction;
use MagicLink\MagicLink;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected string $email;

    public function create(bool $another = false): void
    {
        /** @var Store $store */
        $store = Filament::getTenant();

        $data = $this->form->getState();

        $user = User::where('email', $data['email'])->first();
        $this->email = $data['email'];

        if ($user) {
            $isTheUserACustomer = $user->stores()
                ->wherePivot('store_id', $store->id)
                ->wherePivot('role', 'customer')
                ->exists();

            if ($isTheUserACustomer) {
                Notification::make()
                    ->title('Cliente existente')
                    ->body('El usuario ya está asociado como cliente en esta tienda.')
                    ->warning()
                    ->send();

                return;
            }

            $user->assignRole('customer');
            $user->stores()->attach($store->id, ['role' => 'customer']);
            $this->sendMagicLink($user);

            Notification::make()
                ->title('Cliente registrado')
                ->body('El usuario fue asociado como cliente a la tienda y se le envió un enlace de inicio de sesión.')
                ->success()
                ->send();

            return;
        }

        parent::create();
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Cliente registrado')
            ->body('El cliente fue registrado exitosamente y se le envió un enlace de inicio de sesión.')
            ->success()
            ->send();
    }

    protected function afterCreate(): void
    {
        $user = User::where('email', $this->email)->first();

        if ($user) {
            $store = Filament::getTenant();
            $user->stores()->attach($store->id, ['role' => 'customer']);

            $user->email_verified_at = now();
            $user->save();

            $this->sendMagicLink($user);
        }
    }

    protected function sendMagicLink(User $user): void
    {
        $route = route('filament.app.pages.dashboard');
        $action = new LoginAction($user);
        $action->response(fn () => redirect($route));
        $magicLinkUrl = MagicLink::create($action)->url;

        $store = Filament::getTenant();
        $storeName = $store ? $store->name : 'Nuestra Tienda';

        $user->notify(new WelcomeCustomerNotification($magicLinkUrl, $storeName));
    }
}
