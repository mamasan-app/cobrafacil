<?php

namespace App\Filament\App\Resources\StoreResource\Widgets;

use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class StorePlansWidget extends BaseWidget
{
    public $record;

    protected function getTableHeading(): ?string
    {
        return 'Planes Disponibles en la Tienda';
    }

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getQuery())
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->placeholder('No disponible')
                    ->wrap(),

                Tables\Columns\TextColumn::make('price_cents')
                    ->label('Precio')
                    ->placeholder('No disponible')
                    ->money('USD', 100)
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_text')
                    ->label('Duración')
                    ->placeholder('No disponible')
                    ->sortable(),
            ])
            ->filters([
                // Puedes agregar filtros aquí si lo necesitas.
            ])
            ->actions([
                Action::make('Suscribirse')
                    ->action(function (Plan $record) {
                        $this->subscribeToPlan($record);
                    })
                    ->color('primary')
                    ->icon('heroicon-o-plus')
                    ->label('Suscribirse')
                    ->button(),
            ]);
    }

    protected function getQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $storeId = $this->record->id;

        return Plan::query()
            ->where('store_id', $storeId)
            ->where('published', true);
    }

    protected function subscribeToPlan(Plan $plan): void
    {
        $user = Auth::user();

        if (! $user) {
            throw new \Exception('Debe estar autenticado para suscribirse.');
        }

        $freeDays = $plan->free_days;
        $gracePeriod = $plan->grace_period;
        $frequencyDays = $plan->getFrequencyDays();
        $now = Carbon::now();

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'service_id' => $plan->id,
            'service_name' => $plan->name,
            'service_description' => $plan->description,
            'service_price_cents' => $plan->price_cents,
            'service_free_days' => $freeDays,
            'service_grace_period' => $gracePeriod,
            'frequency_days' => $frequencyDays,
            'status' => \App\Enums\SubscriptionStatusEnum::OnTrial->value,
            'trial_ends_at' => $now->clone()->addDays($freeDays),
            'renews_at' => $now->clone()->addDays($freeDays + $frequencyDays),
            'expires_at' => $now->clone()->addDays($freeDays + $gracePeriod),
        ]);

        // Dentro del método subscribeToPlan
        Notification::make()
            ->title('¡Suscripción creada con éxito!')
            ->success()
            ->body('Te has suscrito al plan: '.$plan->name)
            ->send();

    }
}
