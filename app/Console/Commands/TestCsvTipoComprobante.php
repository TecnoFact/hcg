<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use League\Csv\Reader;

class TestCsvTipoComprobante extends Command
{
    protected $signature = 'test:csv-tipocomprobante';
    protected $description = 'Muestra encabezados reales del archivo c_TipoDeComprobante.csv';

    public function handle()
    {
        $path = storage_path('app/catalogos/cfdi40/c_TipoDeComprobante.csv');

        if (!file_exists($path)) {
            $this->error("Archivo no encontrado: $path");
            return;
        }

        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(3); // Probaremos con línea 4

        $headers = $csv->getHeader();

        $this->info("Encabezados detectados:");
        foreach ($headers as $header) {
            $this->line("- " . $header);
        }
    }
}

