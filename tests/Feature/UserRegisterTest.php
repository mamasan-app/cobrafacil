<?php

use App\Models\Store;
use App\Models\User;

it('does user and store registration with bank account', function () {
    $data = [
        'first_name' => 'Carlos',
        'last_name' => 'Pérez',
        'email' => 'carlos.perez@example.com',
        'phone_number' => '04241234567',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'identity_prefix' => 'V',
        'identity_number' => '12345678',
        'birth_date' => '1990-01-01',
        'address' => 'Av. 1 con Calle 1, Edificio 1, Piso 1, Apartamento 1',
        'selfie_path' => 'selfie.jpg',
        'ci_picture_path' => 'ci.jpg',
        'terms_and_conditions_accepted' => true,
        // Datos de la tienda
        'store_name' => 'Mi Tienda',
        'store_description' => 'Tienda de prueba',
        'short_address' => 'Altamira',
        'long_address' => 'Av. 2 con Calle 2, Edificio 2, Piso 2, Apartamento 2',
        'store_rif_path' => 'rif.jpg',
        'constitutive_document_path' => 'certificate.jpg',
        // Datos de la cuenta bancaria
        'bank_code' => '0102',
        'phone_prefix' => '0412',
        'bank_phone_number' => '3456789',
        'store_identity_number' => 'V12345678',
    ];

    $response = $this->post(route('filament.store.auth.register'), $data);

    $this->assertDatabaseHas('users', [
        'email' => $data['email'],
        'first_name' => $data['first_name'],
        'last_name' => $data['last_name'],
    ]);

    $user = User::where('email', $data['email'])->first();

    $this->assertDatabaseHas('stores', [
        'name' => $data['store_name'],
        'owner_id' => $user->id,
    ]);

    $store = Store::where('name', $data['store_name'])->first();
    $this->assertDatabaseHas('bank_accounts', [
        'store_id' => $store->id,
        'bank_code' => $data['bank_code'],
        'phone_number' => $data['phone_prefix'].$data['bank_phone_number'],
        'identity_number' => $data['store_identity_number'],
        'default_account' => true,
    ]);

    $response->assertRedirect(route('filament.store.auth.login'));
})->todo('Pendiente de implementar');

it('validates that user cant register with an exisiting email', function () {
    User::factory()->create([
        'email' => 'carlos.perez@example.com',
    ]);

    $data = [
        'first_name' => 'Carlos',
        'last_name' => 'Pérez',
        'email' => 'carlos.perez@example.com',
        'phone_number' => '04241234567',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'identity_prefix' => 'V',
        'identity_number' => '12345678',
        'birth_date' => '1990-01-01',
        'address' => 'Av. 1 con Calle 1, Edificio 1, Piso 1, Apartamento 1',
        'selfie_path' => 'selfie.jpg',
        'ci_picture_path' => 'ci.jpg',
        'terms_and_conditions_accepted' => true,
    ];

    $response = $this->post(route('filament.store.auth.login'), $data);
    $this->assertDatabaseCount('users', 1);
    $response->assertSessionHasErrors(['email']);
})->todo('Pendiente de implementar');
