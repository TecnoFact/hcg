<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ObjImpSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // file name objimp.json
        $json = file_get_contents(storage_path('app/catalogos/cfdi40/objimp.json'));
        $data = json_decode($json, true);

        foreach ($data as $item) {
            \App\Models\ObjImp::create([
                'descripcion' => $item['Descripción'],
                'clave' => $item['c_ObjetoImp'],
                'is_active' => true,
            ]);
        }

    }
}
