<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitySeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $path = storage_path('app/catalogos/cfdi40/ciudad.json');

        if (!file_exists($path)) {
            $this->command->error("Archivo no encontrado: $path");
            return;
        }

        $data = json_decode(file_get_contents($path), true);


        DB::table('ciudades')->truncate(); // Limpiar la tabla antes de insertar nuevos datos

        foreach ($data as $record) {


            DB::table('ciudades')->updateOrInsert([
                'id_estado' => $record['c_Estado'] ?: null],
                [
                'descripcion' => $record['Descripción'],
                //'vigencia_desde' => $record['Fecha de inicio de vigencia'] ?: null,
                //'vigencia_hasta' => $record['Fecha de fin de vigencia'] ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        }
    }
}
