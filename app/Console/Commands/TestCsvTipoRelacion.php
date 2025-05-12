<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use League\Csv\Reader;

class TestCsvTipoRelacion extends Command
{
    protected $signature = 'test:csv-tiporelacion';
    protected $description = 'Muestra encabezados reales del archivo c_TipoRelacion.csv';

    public function handle()
    {
        $path = storage_path('app/catalogos/cfdi40/c_TipoRelacion.csv');

        if (!file_exists($path)) {
            $this->error("Archivo no encontrado: $path");
            return;
        }

        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(3); // Estimado, se puede ajustar

        $headers = $csv->getHeader();

        $this->info("Encabezados detectados:");
        foreach ($headers as $header) {
            $this->line("- " . $header);
        }
    }
}

