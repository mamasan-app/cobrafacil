<x-filament::page>
  <div class="space-y-6">
    <x-filament::section>
      <x-slot name="heading">
        Información de la Suscripción
      </x-slot>

      <ul class="space-y-2">
        <li>
          <span class="font-semibold">Estado:</span>
          <span>
            {{ $subscription->status ? ucfirst(str_replace('_', ' ', $subscription->status->value)) : 'Estado no disponible' }}
          </span>
        </li>
        <li>
          <span class="font-semibold">Fin del Período de Prueba:</span>
          <span>
            {{ $subscription->trial_ends_at ? $subscription->trial_ends_at->format('d/m/Y') : 'No disponible' }}
          </span>
        </li>
        <li> <span class="font-semibold">Fecha de Expiración:</span>
          <span>
            {{ $subscription->expires_at ? $subscription->expires_at->format('d/m/Y') : 'No disponible' }}
          </span>
        </li>
        <li> <span class="font-semibold">Precio:</span>
          <span class="font-medium text-green-400">
            {{ $subscription->formattedPrice() }}
          </span>
        </li>
      </ul>
    </x-filament::section>

    <x-filament::section>
      <x-slot name="heading">
        Información del Servicio
      </x-slot>

      @if ($subscription)
        <ul class="space-y-2">
          <li>
            <span class="font-semibold">Nombre del Servicio:</span>
            <span
              class=">{{ $subscription->service_name }}</span> </li> <li> <span class="font-semibold">Descripción:</span>
            <span
              class=">{{ $subscription->service_description }}</span> </li> <li> <span class="font-semibold">Precio:</span>
            <span class="font-medium text-green-400">{{ $subscription->formattedPrice() }}</span>
          </li>
          <li>
            <span class="font-semibold">Días Gratuitos:</span>
            <span>{{ $subscription->service_free_days }} días</span>
          </li>
          <li> <span class="font-semibold">Período
              de Gracia:</span>
            <span>{{ $subscription->service_grace_period }} días</span>
          </li>
        </ul>
      @else
        <p class="text-gray-400">No hay servicio disponible para esta suscripción.</p>
      @endif
    </x-filament::section>
  </div>
</x-filament::page>
