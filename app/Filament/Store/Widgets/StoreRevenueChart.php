<?php

namespace App\Filament\Store\Widgets;

use App\Models\Payment;
use Carbon\CarbonPeriod;
use Filament\Facades\Filament;
use Filament\Widgets\LineChartWidget;

class StoreRevenueChart extends LineChartWidget
{
    protected static ?string $heading = 'Ingresos de la Tienda';

    // Asegura que el gráfico ocupe todo el ancho
    protected int|string|array $columnSpan = 'full';

    // Define una altura mayor para el gráfico
    protected static ?string $maxHeight = '400px';

    protected function getType(): string
    {
        return 'line'; // Tipo de gráfico
    }

    protected function getFilters(): array
    {
        return [
            'last_week' => 'Últimos 7 días',
            'this_month' => 'Este mes',
            'last_month' => 'Mes pasado',
        ];
    }

    protected function getData(): array
    {
        // Obtener el filtro seleccionado
        $activeFilter = $this->filter;

        // Obtener el rango de fechas según el filtro
        [$start, $end] = match ($activeFilter) {
            'this_month' => [
                now('America/Caracas')->startOfMonth()->setTimezone('UTC'),
                now('America/Caracas')->endOfMonth()->setTimezone('UTC'),
            ],
            'last_month' => [
                now('America/Caracas')->subMonth()->startOfMonth()->setTimezone('UTC'),
                now('America/Caracas')->subMonth()->endOfMonth()->setTimezone('UTC'),
            ],
            default => [now()->subDays(7), now()],
        };

        // Obtener el store_id del tenant actual
        $currentStore = Filament::getTenant();

        if (! $currentStore) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        // Consultar los ingresos agrupados por día dentro del rango de fechas
        $revenueData = Payment::whereHas('subscription', function ($query) use ($currentStore) {
            $query->where('store_id', $currentStore->id);
        })
            ->where('status', 'completed') // Solo pagos completados
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, SUM(amount_cents) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Generar un rango de fechas completo para el eje X
        $period = CarbonPeriod::create($start, $end);
        $labels = [];
        $data = [];

        foreach ($period as $date) {
            $formattedDate = $date->format('Y-m-d');
            $labels[] = $date->format('M d'); // Etiquetas del eje X
            $dailyRevenue = $revenueData->firstWhere('date', $formattedDate);
            $data[] = $dailyRevenue ? $dailyRevenue->total / 100 : 0; // Convertir a dólares
        }

        return [
            'datasets' => [
                [
                    'label' => 'Ingresos',
                    'data' => $data,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
