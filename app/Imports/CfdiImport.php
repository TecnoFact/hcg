<?php

namespace App\Imports;

use App\Models\Models\Cfdi;
use App\Models\Models\CfdiEmisor;
use App\Models\Tax;
use App\Services\ComplementoXmlService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Models\CfdiReceptor;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\ToCollection;

class CfdiImport implements ToCollection
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
         $cfdi = null;

        $cfdiAnterior = null;

        foreach($collection as $key => $row) {
            if ($row->isEmpty()) {
                continue; // Skip empty rows
            }


            $tipo = strtoupper(trim($row[0]));

            $total = 0;
            $subtotal = 0;

            if ($tipo === 'CFDI') {

                if ($cfdiAnterior) {
                   $xml = ComplementoXmlService::buildXmlCfdiFromDatabase($cfdiAnterior);

                   // actualizar Cfdi con path
                    // Guarda el XML
                    $name_xml_path = 'CFDI-' . $cfdi->id . '.xml';
                    $path_xml = 'emisiones/' . $name_xml_path;
                    Storage::disk('local')->put($path_xml, $xml);

                    // Actualiza el registro con la ruta del XML
                    $cfdi->update(['path_xml' => $path_xml, 'status_upload' => Cfdi::ESTATUS_SUBIDO]);
                }

                $emisorFind = CfdiEmisor::where('rfc', $row[1])->first();

                if (!$emisorFind) {

                    $emisor = [
                        'rfc' => $row[1],
                        'nombre' => $row[2],
                        'regimen_fiscal' => $row[3],
                    ];

                    $emisorFind = CfdiEmisor::firstOrCreate($emisor);
                }

                $receptorFind = CfdiReceptor::where('rfc', $row[4])->first();

                if (!$receptorFind) {

                    // receptor data store
                    $receptor = [
                        'rfc' => $row[4],
                        'nombre' => $row[5],
                        'regimen_fiscal' => $row[6],
                        'domicilio_fiscal' => $row[7],
                        'uso_cfdi' => $row[8],
                    ];

                    $receptorFind = CfdiReceptor::firstOrCreate($receptor);
                }

                $formaPago = DB::table('catalogo_forma_pago')->where('descripcion', 'LIKE', "%$row[14]%")->first();


                // Soporta formato con hora: 23/07/2025 12:01:02
                $fecha = \DateTime::createFromFormat('d/m/Y H:i:s', trim($row[11]));
                if (!$fecha) {
                    // Si no tiene hora, intenta solo con la fecha
                    $fecha = \DateTime::createFromFormat('d/m/Y', trim($row[11]));
                    $fechaFormateada = $fecha ? $fecha->format('Y-m-d') : null;
                } else {
                    $fechaFormateada = $fecha->format('Y-m-d H:i:s');
                }


                $conceptosQty = 0;

                $cfdi = Cfdi::create([
                    'emisor_id' => $emisorFind->id,
                    'receptor_id' => $receptorFind->id,
                    'serie' => $row[9],
                    'folio' => $row[10],
                    'fecha' => $fechaFormateada,
                    'subtotal' => $row[12],
                    'descuento' => 0,
                    'total' => $row[13],
                    'forma_pago' => $formaPago ? $formaPago->clave : null,
                    'metodo_pago' => $row[15],
                    'moneda' => $row[16],
                    'tipo_de_comprobante' => $row[17],
                    'exportacion' => '01',
                    'user_id' => auth()->id(),
                    'lugar_expedicion' => $row[8],
                ]);


            } elseif ($tipo === 'CONCEPTO' && $cfdi) {

                if (count($row) < 16) {
                    continue; // Skip rows with insufficient data for concept
                }
                // sum +1 qty conceptos
                $conceptosQty++;

                $tax = Tax::where('code', $row[23])->first();
                $subtotal = $row[18] * $row[22]; // Assuming row[18] is quantity and row[22] is unit price
                $totalTax = $row[18] * $row[22] * ($tax ? $tax->rate : 0);

                $total = $subtotal + $totalTax; // Add tax to total

                $claveUnidadFind = DB::table('catalogo_clave_unidad')->where('nombre', 'LIKE', "%$row[20]%")->first();

                $cfdi->conceptos()->create([
                    'clave_prod_serv' => $row[21],
                    'no_identificacion' => $conceptosQty,
                    'cantidad' => $row[18],
                    'clave_unidad' => $claveUnidadFind ? $claveUnidadFind->clave : 26,
                    'unidad' => $row[20],
                    'descripcion' => $row[19],
                    'valor_unitario' => $row[22],
                    'tipo_impuesto' => $row[23],
                    'importe' => $total,
                    'descuento' => 0,
                    'tax_id' => $tax?->id,
                    'obj_imp_id' => null
                ]);

                $cfdiAnterior = $cfdi;
            }


        }

        if ($cfdiAnterior) {
            $xml = ComplementoXmlService::buildXmlCfdiFromDatabase($cfdiAnterior);

            $name_xml_path = 'CFDI-' . $cfdiAnterior->id . '.xml';
            $path_xml = 'emisiones/' . $name_xml_path;
            Storage::disk('local')->put($path_xml, $xml);

            $cfdiAnterior->update(['path_xml' => $path_xml, 'status_upload' => Cfdi::ESTATUS_SUBIDO]);
        }
    }
}
