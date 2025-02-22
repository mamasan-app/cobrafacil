<?php

namespace App\Filament\Store\Pages\Auth;

use App\Filament\Inputs;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;

class EditProfile extends BaseEditProfile
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('first_name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),

                TextInput::make('last_name')
                    ->label('Apellido')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Correo Electrónico')
                    ->email()
                    ->required()
                    ->unique('users', 'email'),

                Inputs\PhoneNumberInput::make('phone_number')
                    ->label('Número de Teléfono')
                    ->required(),

                DatePicker::make('birth_date')
                    ->label('Fecha de Nacimiento'),

                Textarea::make('address')
                    ->label('Dirección'),

                FileUpload::make('selfie_path')
                    ->label('Foto de Perfil')
                    ->image()
                    ->disk(config('filesystems.users'))
                    ->visibility('private')
                    ->maxFiles(1),

                FileUpload::make('ci_picture_path')
                    ->label('Foto de Cédula')
                    ->image()
                    ->disk(config('filesystems.users'))
                    ->visibility('private')
                    ->maxFiles(1),

                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }
}
