<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use League\Csv\Reader;

class TestCsvEstado extends Command
{
    protected $signature = 'test:csv-estado';
    protected $description = 'Detecta encabezados reales del archivo c_Estado.csv';

    public function handle(): void
    {
        $path = storage_path('app/catalogos/cfdi40/c_Estado.csv');

        if (!file_exists($path)) {
            $this->error("Archivo no encontrado: $path");
            return;
        }

        $this->info("Analizando archivo: $path\n");

        for ($i = 0; $i <= 10; $i++) {
            try {
                $csv = Reader::createFromPath($path, 'r');
                $csv->setHeaderOffset($i);
                $headers = $csv->getHeader();

                $this->line("Offset: $i");
                foreach ($headers as $header) {
                    $this->line("- $header");
                }
                $this->line(str_repeat('-', 40));
            } catch (\Throwable $e) {
                $this->error("Error con offset $i: " . $e->getMessage());
            }
        }
    }
}