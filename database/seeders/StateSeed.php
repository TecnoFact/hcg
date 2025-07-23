<?php

namespace Database\Seeders;

use League\Csv\Reader;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class StateSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $path = storage_path('app/catalogos/cfdi40/estado.json');

            if (!file_exists($path)) {
                $this->command->error("Archivo no encontrado: $path");
                return;
            }

            $data = json_decode(file_get_contents($path), true);

            foreach ($data as $record) {

                if($record['c_Pais'] === 'MEX') {
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
}
