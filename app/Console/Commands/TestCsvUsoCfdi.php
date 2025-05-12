<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use League\Csv\Reader;

class TestCsvUsoCfdi extends Command
{
    protected $signature = 'test:csv-usocfdi';
    protected $description = 'Muestra encabezados reales del archivo c_UsoCFDI.csv';

    public function handle()
    {
        $path = storage_path('app/catalogos/cfdi40/c_UsoCFDI.csv');

        if (!file_exists($path)) {
            $this->error("Archivo no encontrado: $path");
            return;
        }

        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(3); // usualmente línea 4

        $headers = $csv->getHeader();

        $this->info("Encabezados detectados:");
        foreach ($headers as $header) {
            $this->line("- " . $header);
        }
    }
}
