<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use League\Csv\Reader;

class TestCsvTipoFactor extends Command
{
    protected $signature = 'test:csv-tipo-factor';
    protected $description = 'Detecta encabezados reales del archivo c_TipoFactor.csv';

    public function handle()
    {
        $path = storage_path('app/catalogos/cfdi40/c_TipoFactor.csv');

        if (!file_exists($path)) {
            $this->error("Archivo no encontrado: $path");
            return;
        }

        $lineas = file($path);
        foreach ($lineas as $i => $linea) {
            $this->info("Offset: $i");
            $columnas = str_getcsv(trim($linea));
            foreach ($columnas as $col) {
                $this->line("- " . $col);
            }
            $this->info(str_repeat('-', 40));
        }
    }
}
