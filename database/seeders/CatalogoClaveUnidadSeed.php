<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CatalogoClaveUnidadSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $db = \DB::table('unit_measures');

        // Verificar si la tabla existe
        if (!$db->exists()) {
            $db->truncate(); // Limpiar la tabla si no existe
        }

        $path = storage_path('app/catalogos/cfdi40/unit_measure.sql');

        if (!file_exists($path)) {
            $this->command->error("Archivo no encontrado: $path");
            return;
        }

            $sql = file_get_contents($path);
            \DB::unprepared($sql);

            $this->command->info('Archivo SQL importado correctamente.');



        $this->command->info('Catalogo Clave Unidad seeded successfully.');
    }
}
