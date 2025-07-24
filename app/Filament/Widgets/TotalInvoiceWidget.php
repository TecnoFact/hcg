<?php

namespace App\Filament\Widgets;

use App\Models\Models\Cfdi;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalInvoiceWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalFacturado = Cfdi::where('user_id', auth()->id())->sum('total');
        $totalFacturado = number_format($totalFacturado, 2, '.', ',');

        return [
            Stat::make('Total Facturado', $totalFacturado)
                ->description('Total Facturado')
                ->icon('heroicon-o-currency-dollar')
                ->color('success'),

            Stat::make('Facturas Emitidas', Cfdi::where('user_id', auth()->id())->count())
                ->description('Total de Facturas emitidas')
                ->icon('heroicon-o-document-text')
                ->color('primary'),

            Stat::make('Total Usuarios', \App\Models\User::where('user_id', auth()->id())->count())
                ->description('Total de Usuarios registrados')
                ->icon('heroicon-o-users')
                ->color('warning'),

            Stat::make('Certificados Vencidos', 0)->description('Certificados que han vencido')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
