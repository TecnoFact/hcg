<?php

namespace App\Filament\Resources\CfdiResource\Pages;

use App\Filament\Resources\CfdiResource;
use App\Models\Emisor;
use App\Models\Models\Cfdi;
use App\Models\Models\CfdiConcepto;
use App\Models\Models\CfdiEmisor;
use App\Models\Models\CfdiReceptor;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

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
            'nombre' => $data['emisor_nombre'],
            'regimen_fiscal' => $data['emisor_regimen_fiscal'],
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

        $emisor = CfdiEmisor::where('rfc', $data['emisor_rfc'])->first();

        if (!$emisor) {
            $emisor = CfdiEmisor::create($data['emisor']);
        } else {
            $emisor->update($data['emisor']);
        }

        // guardar el receptor
        $data['receptor'] = [
            'rfc' => $data['receptor_rfc'],
            'nombre' => $data['receptor_nombre'],
            'domicilio_fiscal' => $data['receptor_domicilio'],
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

        // Registrar los conceptos en la base de datos

        foreach ($conceptos as $concepto) {
            CfdiConcepto::updateOrCreate(
                ['cfdi_id' => $cfdi->id, 'no_identificacion' => $concepto['no_identificacion']],
                array_merge($concepto, ['cfdi_id' => $cfdi->id])
            );
        }

        // Actualizar el subtotal y total del Cfdi
        $total = array_sum(array_column($conceptos, 'importe'));
        $cfdi->update(['subtotal' => $total, 'total' => $total]);



        // Retornar los datos actualizados
        $data['subtotal'] = $total;
        $data['total'] = $total;

        return $data;
    }
}
