<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as FilamentLogin;
use MagicLink\Actions\LoginAction;
use MagicLink\MagicLink;

class UserLogin extends FilamentLogin
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->label('Correo Electrónico')
                    ->required()
                    ->email()
                    ->exists('users', 'email')
                    ->maxLength(255),
            ])
            ->statePath('data');
    }

    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();

        $user = User::where('email', $data['email'])->first();
        $action = new LoginAction($user);

        $magicLinkUrl = MagicLink::create($action)->url;

        $user->notify(new MagicLinkNotification($magicLinkUrl));

        session()->flash('message', 'Se ha enviado un enlace de acceso a tu correo.');

        Notification::make()
            ->title('¡Enlace enviado!')
            ->body('Se ha enviado un enlace mágico a tu correo electrónico. Revisa tu bandeja de entrada para continuar.')
            ->success()
            ->send();

        return null;
    }

    protected function getAuthenticateFormAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('sendMagicLink')
            ->label('Enviar Enlace Mágico')
            ->submit('authenticate');
    }

    public function submit()
    {
        $this->authenticate();
    }
}
