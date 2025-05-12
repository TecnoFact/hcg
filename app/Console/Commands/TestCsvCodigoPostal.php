<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use League\Csv\Reader;

class TestCsvCodigoPostal extends Command
{
    protected $signature = 'test:csv-codigo-postal';
    protected $description = 'Muestra los encabezados del catálogo c_CodigoPostal.csv para verificación';

    public function handle()
    {
        $path = storage_path('app/catalogos/cfdi40/c_CodigoPostal.csv');

        if (!file_exists($path)) {
            $this->error('No se encontró el archivo: ' . $path);
            return;
        }

        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(3); // Línea 4

        $headers = $csv->getHeader();
        $this->info("Encabezados detectados:");
        foreach ($headers as $header) {
            $this->line("- " . $header);
        }
    }
}
