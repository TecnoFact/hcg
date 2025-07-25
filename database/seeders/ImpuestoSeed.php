<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ImpuestoSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // file name impuestos.json
        $json = file_get_contents(storage_path('app/catalogos/cfdi40/impuesto.json'));
        $data = json_decode($json, true);

        foreach ($data as $item) {
            \App\Models\Tax::create([
                'name' => $item['Descripción'],
                'code' => $item['Descripción'],
                'rate' => 0,
                'is_active' => true,

            ]);
        }
    }
}
