<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use League\Csv\Reader;

class TestCsvMetodoPago extends Command
{
    protected $signature = 'test:csv-metodopago';
    protected $description = 'Muestra encabezados reales del archivo c_MetodoPago.csv';

    public function handle()
    {
        $path = storage_path('app/catalogos/cfdi40/c_MetodoPago.csv');

        if (!file_exists($path)) {
            $this->error("Archivo no encontrado: $path");
            return;
        }

        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(3); // Puedes ajustar si vemos que no es la línea correcta

        $headers = $csv->getHeader();

        $this->info("Encabezados detectados:");
        foreach ($headers as $header) {
            $this->line("- " . $header);
        }
    }
}
