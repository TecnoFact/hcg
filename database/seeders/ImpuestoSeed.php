<?php

namespace Database\Seeders;

use App\Models\Tax;
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

            if($item['Descripción'] === 'IVA') {
                $code = '002';
            } elseif($item['Descripción'] === 'ISR') {
                $code = '001';
            } else {
                $code = '003';
            }

            Tax::firstOrCreate([
                'name' => $item['Descripción'],
                'code' => $code,
                'rate' => 0,
                'is_active' => true,

            ]);
        }
    }
}
