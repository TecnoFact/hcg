<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use League\Csv\Reader;

class TestCsvClaveProdServ extends Command
{
    protected $signature = 'test:claveprodserv';
    protected $description = 'Muestra encabezados reales del archivo c_ClaveProdServ.csv';

    public function handle()
    {
        $path = storage_path('app/catalogos/cfdi40/c_ClaveProdServ.csv');

        if (!file_exists($path)) {
            $this->error("Archivo no encontrado: $path");
            return;
        }

        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(4); // Línea 5

        $headers = $csv->getHeader();

        $this->info("Encabezados detectados:");
        foreach ($headers as $header) {
            $this->line("- " . $header);
        }
    }
}
