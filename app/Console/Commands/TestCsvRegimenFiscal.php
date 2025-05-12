<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use League\Csv\Reader;

class TestCsvRegimenFiscal extends Command
{
    protected $signature = 'test:csv-regimenfiscal';
    protected $description = 'Muestra encabezados reales del archivo c_RegimenFiscal.csv';

    public function handle()
    {
        $path = storage_path('app/catalogos/cfdi40/c_RegimenFiscal.csv');

        if (!file_exists($path)) {
            $this->error("Archivo no encontrado: $path");
            return;
        }

        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(4); // usualmente línea 5

        $headers = $csv->getHeader();

        $this->info("Encabezados detectados:");
        foreach ($headers as $header) {
            $this->line("- " . $header);
        }
    }
}
