<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use League\Csv\Reader;

class TestCsvPais extends Command
{
    protected $signature = 'test:csv-pais';
    protected $description = 'Muestra encabezados del archivo c_Pais.csv por offset';

    public function handle()
    {
        $path = storage_path('app/catalogos/cfdi40/c_Pais.csv');
        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0);

        $records = iterator_to_array($csv->getRecords());

        for ($i = 0; $i < 10; $i++) {
            $row = $records[$i] ?? null;
            if ($row) {
                $this->info("Offset $i:");
                $this->line(implode(' | ', array_keys($row)));
                $this->line(str_repeat('-', 40));
            }
        }
    }
}

