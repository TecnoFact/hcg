<?php

namespace App\Filament\Widgets;

use App\Models\Emisor;
use App\Models\Models\Cfdi;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalInvoiceWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalFacturado = Cfdi::where('user_id', auth()->id())->sum('total');
        $totalFacturado = number_format($totalFacturado, 2, '.', ',');


        $certificadosVencidos = Emisor::where('user_id', auth()->id())
            ->whereDate('due_date', '<', now()->toDateString())
            ->count();

        $stats = [
            Stat::make('Total Facturado', $totalFacturado)
                ->description('Total Facturado')
                ->icon('heroicon-o-currency-dollar')
                ->color('success'),

            Stat::make('Facturas Emitidas', Cfdi::where('user_id', auth()->id())->count())
                ->description('Total de Facturas emitidas')
                ->icon('heroicon-o-document-text')
                ->color('primary'),
        ];

        if (
            auth()->user()->hasRole('Admin')
        ) {
            $stats[] = Stat::make('Total Usuarios', \App\Models\User::count())
                ->description('Total de Usuarios registrados')
                ->icon('heroicon-o-users')
                ->color('warning');
        }

        $stats[] = Stat::make('Certificados Vencidos', $certificadosVencidos)
            ->description('Certificados que han vencido')
            ->icon('heroicon-o-exclamation-triangle')
            ->color('danger');

        return $stats;
    }
}
