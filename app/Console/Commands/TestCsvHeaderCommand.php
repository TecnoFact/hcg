<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use League\Csv\Reader;

class TestCsvHeaderCommand extends Command
{
    protected $signature = 'test:csv-regimen';
    protected $description = 'Test encabezados del CSV c_RegimenFiscal';

    public function handle()
    {
        $path = storage_path('app/catalogos/cfdi40/c_RegimenFiscal.csv');

        try {
            $csv = Reader::createFromPath($path, 'r');
            $csv->setHeaderOffset(4); // línea 5

            $headers = $csv->getHeader(); // esto es lo que Laravel ve como encabezado

            $this->info("Encabezados detectados:");
            foreach ($headers as $header) {
                $this->line("- " . $header);
            }
        } catch (\Throwable $e) {
            $this->error("Error leyendo encabezados: " . $e->getMessage());
        }
    }
}
