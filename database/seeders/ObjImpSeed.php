<?php

namespace Database\Seeders;

use App\Models\ObjImp;
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
            ObjImp::firstOrCreate([
                'descripcion' => $item['Descripción'],
                'clave' => $item['c_ObjetoImp'],
                'is_active' => true,
            ]);
        }

    }
}
