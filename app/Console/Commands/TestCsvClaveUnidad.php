<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use League\Csv\Reader;

class TestCsvClaveUnidad extends Command
{
    protected $signature = 'test:csv-claveunidad';
    protected $description = 'Muestra encabezados reales del archivo c_ClaveUnidad.csv';

    public function handle()
    {
        $path = storage_path('app/catalogos/cfdi40/c_ClaveUnidad.csv');

        if (!file_exists($path)) {
            $this->error("Archivo no encontrado: $path");
            return;
        }

        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(4); // Ajustable según resultado

        $headers = $csv->getHeader();

        $this->info("Encabezados detectados:");
        foreach ($headers as $header) {
            $this->line("- " . $header);
        }
    }
}

