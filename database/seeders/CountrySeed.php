<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class CountrySeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = storage_path('app/catalogos/cfdi40/paises.json');

        if (!file_exists($path)) {
            $this->command->error("Archivo no encontrado: $path");
            return;
        }


        $json = json_decode(file_get_contents($path), true);

        foreach ($json as $record) {

            DB::table('catalogo_pais')->insert([
                'clave' => $record['c_Pais'],
                'nombre' => $record['Descripción'],
                'nacionalidad' => $record['Nacionalidad'] ?? 'null',
                'vigencia_desde' => null,
                'vigencia_hasta' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
