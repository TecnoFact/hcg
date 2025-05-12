<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use League\Csv\Reader;

class TestCsvFormaPago extends Command
{
    protected $signature = 'test:csv-formapago';
    protected $description = 'Muestra encabezados reales del archivo c_FormaPago.csv';

    public function handle()
    {
        $path = storage_path('app/catalogos/cfdi40/c_FormaPago.csv');

        if (!file_exists($path)) {
            $this->error("Archivo no encontrado: $path");
            return;
        }

        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(3); // Puedes cambiar este número para probar si el encabezado está en otra línea

        $headers = $csv->getHeader();

        $this->info("Encabezados detectados:");
        foreach ($headers as $header) {
            $this->line("- " . $header);
        }
    }
}
