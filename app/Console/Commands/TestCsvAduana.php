<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use League\Csv\Reader;

class TestCsvAduana extends Command
{
    protected $signature = 'test:csv-aduana';
    protected $description = 'Detecta encabezados reales del archivo c_Aduana.csv';

    public function handle()
    {
        $path = storage_path('app/catalogos/cfdi40/c_Aduana.csv');

        if (!file_exists($path)) {
            $this->error("Archivo no encontrado: " . $path);
            return;
        }

        try {
            for ($offset = 0; $offset <= 5; $offset++) {
                $csv = Reader::createFromPath($path, 'r');
                $csv->setHeaderOffset($offset);
                $headers = $csv->getHeader();

                $this->info("Offset: $offset");
                foreach ($headers as $header) {
                    $this->line("- " . $header);
                }
                $this->line(str_repeat('-', 40));
            }
        } catch (\Exception $e) {
            $this->error("Error leyendo el CSV: " . $e->getMessage());
        }
    }
}
