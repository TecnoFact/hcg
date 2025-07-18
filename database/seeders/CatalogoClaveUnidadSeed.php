<?php

namespace Database\Seeders;

use League\Csv\Reader;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CatalogoClaveUnidadSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $db = \DB::table('catalogo_clave_unidad');

        $path = storage_path('app/catalogos/cfdi40/c_ClaveUnidad.csv');

        if (!file_exists($path)) {
            $this->command->error("Archivo no encontrado: $path");
            return;
        }

          // Cargar el archivo saltando las 3 primeras líneas
        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(3); // encabezado real en la línea 4

        foreach ($csv->getRecords() as $record) {
            // Validar que sea un valor de clave válida (ej: "01", "03", etc.)
            if (!isset($record['c_ClaveUnidad']) || !is_numeric($record['c_ClaveUnidad'])) {
                continue; // saltar registros inválidos
            }

            $db->updateOrInsert([
                'clave' => $record['c_ClaveUnidad'],
            ], [
                'nombre' => $record['Nombre'],
                'descripcion' => $record['Descripción'],
                'nota' => $record['Nota'] ?? null,
                'vigencia_desde' => $record['Fecha de inicio de vigencia'] ?: null,
                'vigencia_hasta' => $record['Fecha de fin de vigencia'] ?: null,
                'simbolo' => $record['Símbolo'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }


        $this->command->info('Catalogo Clave Unidad seeded successfully.');
    }
}
