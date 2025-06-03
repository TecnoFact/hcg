<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use League\Csv\Reader;

class StateSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $path = storage_path('app/catalogos/cfdi40/c_Estado.csv');

            if (!file_exists($path)) {
                $this->command->error("Archivo no encontrado: $path");
                return;
            }

            // Cargar el archivo saltando las 3 primeras líneas
            $csv = Reader::createFromPath($path, 'r');
            $csv->setHeaderOffset(3); // encabezado real en la línea 4

            foreach ($csv->getRecords() as $record) {
                // Validar que sea un valor de clave válida (ej: "01", "03", etc.)
                if (!isset($record['c_Estado']) || !is_numeric($record['c_Estado'])) {
                    continue; // saltar registros inválidos
                }

                DB::table('catalogo_estado')->updateOrInsert([
                    'clave' => $record['c_Estado'],
                ], [
                    'nombre' => $record['Nombre del estado'] ?? 'null',
                    'pais' => $record['c_Pais'] ?? 'null',
                    'vigencia_desde' => null,
                    'vigencia_hasta' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
    }
}
