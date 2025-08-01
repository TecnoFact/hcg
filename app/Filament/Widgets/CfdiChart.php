<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use App\Models\Models\Cfdi;
use Filament\Widgets\ChartWidget;
use Filament\Forms\Components\DatePicker;

class CfdiChart extends ChartWidget
{
    protected static ? string $heading = 'Grafica Emisión de CFDI';
    protected static bool $hasForm = true;

    public ?string $filter = 'today';
    public ?string $fecha_inicio = null;
    public ?string $fecha_fin = null;

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Hoy',
            'week' => 'Última semana',
            'month' => 'Último mes',
            'year' => 'Este año',
        ];
    }

      protected function getFormSchema(): array
    {
        return [
            DatePicker::make('fecha_inicio'),
            DatePicker::make('fecha_fin'),
        ];
    }

protected function getData(): array
{
    [$inicioMes, $finMes] = $this->getDateRange();

    if ($inicioMes->gt($finMes)) {
        [$inicioMes, $finMes] = [$finMes, $inicioMes];
    }

    $diasDelMes = $inicioMes->diffInDays($finMes) + 1;

    if ($diasDelMes <= 0) {
        return [
            'labels' => [],
            'datasets' => []
        ];
    }

    // Generar días como colección
    $dias = collect();
    for ($i = 0; $i < $diasDelMes; $i++) {
        $dias->push($inicioMes->copy()->addDays($i)->format('d'));
    }

    // Función modificada para asegurar consistencia en los datos
    $consultarCfdis = function($estado = null) use ($inicioMes, $finMes) {
        $resultados = Cfdi::query()
            ->selectRaw('DAY(created_at) as dia, COUNT(*) as total')
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->when($estado, fn($q) => $q->where('estatus_upload', $estado))
            ->groupBy('dia')
            ->get()
            ->mapWithKeys(function($item) {
                return [str_pad($item->dia, 2, '0', STR_PAD_LEFT) => $item->total];
            })
            ->all(); // Convertimos a array asociativo

        // Aseguramos que todos los días tengan valor
        $datosCompletos = [];
        foreach (range(1, 31) as $dia) {
            $diaStr = str_pad($dia, 2, '0', STR_PAD_LEFT);
            $datosCompletos[$diaStr] = $resultados[$diaStr] ?? 0;
        }

        return $datosCompletos;
    };

    // Obtener datos
    $datos = [
        'subido' => $consultarCfdis(),
        'sellado' => $consultarCfdis('sellado'),
        'timbrado' => $consultarCfdis('timbrado'),
        'depositado' => $consultarCfdis('depositado')
    ];

    \Log::debug('Datos CFDI', [
        'fecha_inicio' => $inicioMes->format('Y-m-d'),
        'fecha_fin' => $finMes->format('Y-m-d'),
        'dias_generados' => $dias->all(),
        'datos_emitidos' => $datos['subido'],
        'datos_timbrados' => $datos['timbrado']
    ]);

    // Preparar respuesta
    return [
        'labels' => $dias->map(fn($d) => $d.'/'.$inicioMes->format('m'))->all(),
        'datasets' => [
            [
                'label' => 'Subido',
                'data' => $dias->map(fn($dia) => (int) ($datos['subido'][$dia] ?? 0))->all(),
                'borderColor' => '#3b82f6'
            ],
            [
                'label' => 'Sellado',
                'data' => $dias->map(fn($dia) => (int) ($datos['sellado'][$dia] ?? 0))->all(),
                'borderColor' => '#10b981'
            ],
            [
                'label' => 'Timbrado',
                'data' => $dias->map(fn($dia) => (int) ($datos['timbrado'][$dia] ?? 0))->all(),
                'borderColor' => '#f59e0b'
            ],
            [
                'label' => 'Depositado',
                'data' => $dias->map(fn($dia) => (int) ($datos['depositado'][$dia] ?? 0))->all(),
                'borderColor' => '#ef4444'
            ]
        ]
    ];
}

    protected function getDateRange(): array
    {
        if ($this->fecha_inicio && $this->fecha_fin) {
            $inicio = Carbon::parse($this->fecha_inicio)->startOfDay();
            $fin = Carbon::parse($this->fecha_fin)->endOfDay();

            return [$inicio, $fin];
        }

        return match ($this->filter) {
            'today' => [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()],
            'week' => [Carbon::now()->subWeek()->startOfDay(), Carbon::now()->endOfDay()],
            'month' => [Carbon::now()->subMonth()->startOfDay(), Carbon::now()->endOfDay()],
            'year' => [Carbon::now()->startOfYear(), Carbon::now()->endOfDay()],
            default => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
        };
    }

    protected function getType(): string
    {
        return 'line';
    }
}
