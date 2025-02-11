<x-filament::page>
    <!-- Contenedor del formulario -->
    <div class="space-y-6">
        <form wire:submit.prevent="submit">
            <!-- Formulario -->
            <div class="space-y-4">
                {{ $this->form }}
            </div>

            <!-- Contenedor del botón -->
            <div class="mt-6">
                <x-filament::button type="submit" color="primary">
                    {{ $this->buttonLabel }} <!-- Texto dinámico del botón -->
                </x-filament::button>
            </div>
        </form>

        <!-- Botón adicional para reiniciar -->
        <div class="mt-4">
            <x-filament::button type="button" wire:click="resetForm" color="gray">
                Reiniciar
            </x-filament::button>
        </div>
    </div>
</x-filament::page>