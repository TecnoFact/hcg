<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class RegimenFiscalSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = storage_path('app/catalogos/cfdi40/c_RegimenFiscal.csv');

        if (!file_exists($path)) {
            $this->command->error("Archivo no encontrado: $path");
            return;
        }

        // Cargar el archivo saltando las 3 primeras líneas
        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(3); // encabezado real en la línea 4

        foreach ($csv->getRecords() as $record) {
            // Validar que sea un valor de clave válida (ej: "01", "03", etc.)
            if (!isset($record['c_RegimenFiscal']) || !is_numeric($record['c_RegimenFiscal'])) {
                continue; // saltar registros inválidos
            }

            DB::table('catalogo_regimen_fiscal')->updateOrInsert([
                'clave' => $record['c_RegimenFiscal'],
            ], [
                'descripcion' => $record['Descripción'],
                'persona_fisica' => isset($record['Física']) && trim($record['Física']) === 'Sí',
                'persona_moral' => isset($record['Moral']) && trim($record['Moral']) === 'Sí',
                'vigencia_desde' => $record['Fecha de inicio de vigencia'] ?: null,
                'vigencia_hasta' => $record['Fecha de fin de vigencia'] ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
