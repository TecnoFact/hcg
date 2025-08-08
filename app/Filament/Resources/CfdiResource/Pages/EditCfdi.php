<?php

namespace App\Filament\Resources\CfdiResource\Pages;

use App\Filament\Resources\CfdiResource;
use App\Models\Emisor;
use App\Models\Models\Cfdi;
use App\Models\Models\CfdiConcepto;
use App\Models\Models\CfdiReceptor;
use App\Services\ComplementoXmlService;
use App\Services\TimbradoService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditCfdi extends EditRecord
{
    protected static string $resource = CfdiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        //dd($data);

        // emisor find
        $emisor = Emisor::find($data['emisor_id']);

        if ($emisor) {
            $data['emisor_rfc'] = $emisor->rfc;
            $data['emisor_nombre'] = $emisor->name;
            $data['emisor_regimen_fiscal'] = $emisor->tax_regimen_id;
            $data['lugar_expedicion'] = $emisor->postal_code;
        }

        // receptor
        $receptor = CfdiReceptor::find($data['receptor_id']);

        if ($receptor) {
            $data['receptor_rfc'] = $receptor->rfc;
            $data['receptor_nombre'] = $receptor->nombre;
            $data['receptor_domicilio'] = $receptor->domicilio_fiscal;
            $data['receptor_regimen_fiscal'] = $receptor->regimen_fiscal;
            $data['receptor_uso_cfdi'] = $receptor->uso_cfdi;
        }

            // conceptos

            $cfdi = Cfdi::find($data['id']);

            if ($cfdi) {
                $data['conceptos'] = $cfdi->conceptos->toArray();
            } else {
                $data['conceptos'] = [];
            }

            $contador = 1;
            $total = 0;
             foreach ($data['conceptos'] as $i => $concepto) {

                $concepto['no_identificacion'] = $contador++;
                $cantidad = isset($concepto['cantidad']) ? (float) $concepto['cantidad'] : 0;
                $valorUnitario = isset($concepto['valor_unitario']) ? (float) $concepto['valor_unitario'] : 0;
                $importe = $cantidad * $valorUnitario;

                $concepto['importe'] = $importe;
                $concepto['obj_imp_id'] = $concepto['obj_imp_id'] ?? null; // Asegura que obj_imp_id esté presente
                $total += $importe;
            }



        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['user_id'] = auth()->id();

         $data['subtotal'] = 0;
        //$data['ruta'] = 'emisiones/' . $data['nombre_archivo'];
        $data['iva'] = 0;
        $data['total'] = 0;


        // guardar el emisor
        $data['emisor'] = [
            'rfc' => $data['emisor_rfc'],
            'name' => $data['emisor_nombre'],
            'tax_regimen_id' => $data['emisor_regimen_fiscal'],
            'postal_code' => $data['lugar_expedicion'],
        ];

        $total = 0;
        $contador = 1;

        if (isset($data['conceptos']) && is_array($data['conceptos'])) {
            foreach ($data['conceptos'] as $i => $concepto) {
                $concepto['no_identificacion'] = $contador++;
                $cantidad = isset($concepto['cantidad']) ? (float) $concepto['cantidad'] : 0;
                $valorUnitario = isset($concepto['valor_unitario']) ? (float) $concepto['valor_unitario'] : 0;
                $importe = $cantidad * $valorUnitario;
                $data['conceptos'][$i]['importe'] = $importe;
                $total += $importe;
            }
        }


        $data['subtotal'] = $total; // Asigna el subtotal
        $data['total'] = $total;

        $emisor = Emisor::where('rfc', $data['emisor_rfc'])->first();

        if (!$emisor) {
            $emisor = Emisor::create($data['emisor']);
        } else {
            $emisor->update($data['emisor']);
        }

        // guardar el receptor
        $data['receptor'] = [
            'rfc' => $data['receptor_rfc'],
            'nombre' => $data['receptor_nombre'],
            'domicilio_fiscal' => $data['receptor_domicilio'] ?? '0',
            'regimen_fiscal' => $data['receptor_regimen_fiscal'],
            'uso_cfdi' => $data['receptor_uso_cfdi']
        ];

        $receptorFind = CfdiReceptor::where('rfc', $data['receptor_rfc'])->first();

        if (!$receptorFind) {
            $receptorFind = CfdiReceptor::create($data['receptor']);
        } else {
            $receptorFind->update($data['receptor']);
        }

        $data['receptor_id'] = $receptorFind->id;

        if($data['status_upload'] === null || empty($data['status_upload'])) {
            $data['status_upload'] = Cfdi::ESTATUS_SUBIDO;
            $data['estatus'] = 'validado';
        }

        return $data;
    }

    // actualizar conceptos desde cfdi
    protected function afterSave(): array
    {
        $data = $this->data;

        // Actualizar conceptos desde el Cfdi
        $cfdi = $this->record; // Modelo Cfdi recién editado

        // Obtener los conceptos del formulario
        $conceptos = $data['conceptos'] ?? [];

        $conceptos = array_map(function ($concepto) {
            // Convert formatted string to float (e.g. '200,000.00' => 200000.00)
            $concepto['valor_unitario'] = isset($concepto['valor_unitario'])
                ? (float) str_replace([',', ' '], '', $concepto['valor_unitario'])
                : 0;

            return $concepto;
        }, $conceptos);



        // Registrar los conceptos en la base de datos
        $subtotal = 0;
        $total = 0;
        $iva = 0;
        foreach ($conceptos as $concepto) {
            $concepto['obj_imp_id'] = $concepto['obj_imp_id'] ?? null; // Asegura que obj_imp_id esté presente
            CfdiConcepto::updateOrCreate(
                ['cfdi_id' => $cfdi->id, 'no_identificacion' => $concepto['no_identificacion']],
                array_merge($concepto, ['cfdi_id' => $cfdi->id])
            );

            $valor_unitario = isset($concepto['valor_unitario'])
                ? (float) str_replace([',', ' '], '', $concepto['valor_unitario'])
                : 0;

            $tax = \App\Models\Tax::find($concepto['tipo_impuesto']);
            $calculoImporte = 0;
            if ($tax) {
                $calculoImporte += ($concepto['cantidad'] * $valor_unitario) * ($tax->rate / 100);
            }

            // find cfdi from update total, subtotal, iva
            $subtotal += $concepto['cantidad'] * $valor_unitario ?? 0;
            $iva += $calculoImporte ?? 0;
            $total += $subtotal + $iva;
        }

        // Actualizar el subtotal y total del Cfdi
        $total = array_sum(array_column($conceptos, 'importe'));
        $cfdi->update(['subtotal' => $total, 'total' => $total, 'impuesto' => $iva]);

        // Retornar los datos actualizados
        $data['subtotal'] = $total;
        $data['total'] = $total;

        $name_xml_path = 'CFDI-' . $cfdi->id . '.xml';
        $path_xml = $cfdi->emisor->rfc .'/' . $name_xml_path;
        $ruta = 'cfdi/' . $path_xml;

        // Prepara los datos para el servicio
        $data = [
            'cfdi' => $cfdi,
            'conceptos' => $conceptos
        ];

        // Genera el XML
        $xml = ComplementoXmlService::buildXmlCfdi($data);
        TimbradoService::createCfdiSimpleToPDF($cfdi);

        // Guarda el XML
        Storage::disk('local')->put($path_xml, $xml);
        Storage::disk('public')->put($ruta, $xml);



        // Guarda el XML
         $name_xml_path = 'CFDI-' . $cfdi->id . '.xml';
        $path_xml =  $cfdi->emisor->rfc .'/' . $name_xml_path;
        $ruta = 'cfdi/' . $path_xml;

        Storage::disk('local')->put($ruta, $xml);

        // Actualiza el registro con la ruta del XML
          $cfdi->update(['path_xml' => $ruta]);
        $cfdi->update(['nombre_archivo' => $name_xml_path]);
        $cfdi->update(['ruta' => $ruta]);

        return $data;
    }
}
