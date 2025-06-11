<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class CatalogoFormaPagoSeeder extends Seeder
{
    public function run(): void
    {
        $path = storage_path('app/catalogos/cfdi40/c_FormaPago.csv');

        if (!file_exists($path)) {
            $this->command->error("Archivo no encontrado: $path");
            return;
        }

        // Cargar el archivo saltando las 3 primeras líneas
        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(3); // encabezado real en la línea 4

        foreach ($csv->getRecords() as $record) {
            // Validar que sea un valor de clave válida (ej: "01", "03", etc.)
            if (!isset($record['c_FormaPago']) || !is_numeric($record['c_FormaPago'])) {
                continue; // saltar registros inválidos
            }

            DB::table('catalogo_forma_pago')->updateOrInsert([
                'clave' => $record['c_FormaPago'],
            ], [
                'descripcion' => $record['Descripción'],
                'requiere_cuenta_ordenante' => strtolower($record['Patrón para cuenta ordenante']) === 'si',
                'requiere_cuenta_beneficiario' => strtolower($record['Patrón para cuenta Beneficiaria']) === 'si',
                'vigencia_desde' => $record['Fecha inicio de vigencia'] ?: null,
                'vigencia_hasta' => $record['Fecha fin de vigencia'] ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

    }
}


