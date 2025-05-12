<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Los comandos Artisan personalizados.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\TestCsvClaveProdServ::class,
        \App\Console\Commands\TestCsvFormaPago::class,
        \App\Console\Commands\TestCsvMetodoPago::class,
        \App\Console\Commands\TestCsvTipoComprobante::class,
        \App\Console\Commands\TestCsvUsoCfdi::class,
        \App\Console\Commands\TestCsvRegimenFiscal::class,
        \App\Console\Commands\TestCsvClaveUnidad::class,
        \App\Console\Commands\TestCsvTipoRelacion::class,
        \App\Console\Commands\TestCsvCodigoPostal::class,
        \App\Console\Commands\TestCsvAduana::class,
        \App\Console\Commands\TestCsvEstado::class,
        \App\Console\Commands\TestCsvTipoFactor::class,
        \App\Console\Commands\TestCsvPais::class,


    ];


    /**
     * Definir el programador de comandos.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Puedes agendar comandos aquí si lo deseas
    }

    /**
     * Registrar los comandos para la aplicación.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
