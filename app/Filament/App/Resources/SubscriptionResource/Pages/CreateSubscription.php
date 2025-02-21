<?php

namespace App\Filament\App\Resources\SubscriptionResource\Pages;

use App\Enums\SubscriptionStatusEnum;
use App\Filament\App\Resources\SubscriptionResource;
use App\Models\Plan;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSubscription extends CreateRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Obtener el usuario autenticado
        $currentUser = Auth::user();

        if (! $currentUser) {
            throw new \Exception('No se encontró un usuario autenticado.');
        }

        // Asignar el user_id al cliente autenticado
        $data['user_id'] = $currentUser->id;

        // Obtener el Plan relacionado
        $plan = Plan::with('frequency')->find($data['service_id']); // Asegúrate de cargar la relación 'frequency'

        if (! $plan) {
            throw new \Exception('El servicio seleccionado no se encontró.');
        }

        // Obtener datos del Plan
        $freeDays = (int) $plan->free_days; // Días gratuitos del Plan
        $gracePeriod = (int) $plan->grace_period; // Período de gracia
        $serviceName = $plan->name;
        $serviceDescription = $plan->description;
        $servicePriceCents = $plan->price_cents;

        // Obtener datos de la frecuencia
        $frequencyDays = $plan->getFrequencyDays();

        if ($frequencyDays === 0) {
            throw new \Exception('La frecuencia asociada al plan no es válida.');
        }

        // Calcular fechas importantes
        $nowInCaracas = Carbon::now('America/Caracas');
        $trialEndsAt = $nowInCaracas->clone()->addDays($freeDays);
        $expiresAt = $nowInCaracas->clone()->addDays($gracePeriod + $freeDays);

        // Llenar datos adicionales
        $data['status'] = SubscriptionStatusEnum::OnTrial->value; // Estado inicial
        $data['trial_ends_at'] = $trialEndsAt;
        $data['renews_at'] = $trialEndsAt;
        $data['expires_at'] = $expiresAt;

        // Datos desnormalizados del Plan
        $data['service_name'] = $serviceName;
        $data['service_description'] = $serviceDescription;
        $data['service_price_cents'] = $servicePriceCents;
        $data['service_free_days'] = $freeDays;
        $data['service_grace_period'] = $gracePeriod;

        // Datos desnormalizados de la Frecuencia
        $data['frequency_days'] = $frequencyDays;

        return $data;
    }

    protected function afterCreate(): void
    {
        // Obtener el plan relacionado
        $plan = Plan::find($this->record->service_id);

        // Enviar la notificación
        Notification::make()
            ->title('¡Suscripción creada con éxito!')
            ->success()
            ->body('Te has suscrito al plan: '.($plan->name ?? 'Plan desconocido'))
            ->send();
    }
}
