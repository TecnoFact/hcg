<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class CitySeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $path = storage_path('app/catalogos/cfdi40/C_Localidad.csv');

        if (!file_exists($path)) {
            $this->command->error("Archivo no encontrado: $path");
            return;
        }

        // Cargar el archivo saltando las 3 primeras líneas
        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(3); // encabezado real en la línea 4

        foreach ($csv->getRecords() as $record) {
            // Validar que sea un valor de clave válida (ej: "01", "03", etc.)
            if (!isset($record['c_Localidad']) || !is_numeric($record['c_Localidad'])) {
                continue; // saltar registros inválidos
            }

            DB::table('ciudades')->updateOrInsert([
                'id' => $record['c_Localidad'],
            ], [
                'id_estado' => $record['c_Estado'] ?: null,
                'descripcion' => $record['Descripción'],
                //'vigencia_desde' => $record['Fecha de inicio de vigencia'] ?: null,
                //'vigencia_hasta' => $record['Fecha de fin de vigencia'] ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
