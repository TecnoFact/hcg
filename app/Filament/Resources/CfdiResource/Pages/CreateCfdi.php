<?php

namespace App\Filament\Resources\CfdiResource\Pages;

use App\Models\Models\Cfdi;
use App\Services\TimbradoService;
use Filament\Actions\Action;
use App\Models\Models\CfdiEmisor;
use App\Models\Models\CfdiConcepto;
use App\Models\Models\CfdiReceptor;
use App\Services\ComplementoXmlService;
use Illuminate\Support\Facades\Storage;
use App\Filament\Resources\CfdiResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCfdi extends CreateRecord
{
    protected static string $resource = CfdiResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'El Cfdi a sido creado con exito';
    }

     protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        //$data['nombre_archivo'] = 'CFDI-' . ($data['user_id'] + 1) . '.xml'; // Genera un nombre único basado en el ID del usuario
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
        $data['subtotal'] = $total; // Asigna el subtotal
        $data['total'] = $total;

        CfdiEmisor::create($data['emisor']);

        // guardar el receptor
        $data['receptor'] = [
            'rfc' => $data['receptor_rfc'],
            'nombre' => $data['receptor_nombre'],
            'domicilio_fiscal' => $data['receptor_domicilio'],
            'regimen_fiscal' => $data['receptor_regimen_fiscal'],
            'uso_cfdi' => $data['receptor_uso_cfdi']
        ];

        $receptorCreate = CfdiReceptor::create($data['receptor']);

        $data['receptor_id'] = $receptorCreate->id;

        return $data;
    }

    // Genera el XML después de crear el Cfdi y sus conceptos
    protected function afterCreate(): void
    {
        $cfdi = $this->record; // Modelo Cfdi recién creado

        // Obtener los conceptos del formulario
        $conceptos = $this->data['conceptos'] ?? [];

        // Registrar los conceptos en la base de datos
        $contador = 1;
        $subtotal = 0;
        $total = 0;
        $iva = 0;
        foreach ($conceptos as $concepto) {
            // Convert formatted string to float (e.g. '200,000.00' => 200000.00)
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

            CfdiConcepto::create([
                'cfdi_id' => $cfdi->id,
                'no_identificacion' => $contador ?? null,
                'clave_prod_serv' => $concepto['clave_prod_serv'] ?? null,
                'cantidad' => $concepto['cantidad'] ?? null,
                'clave_unidad' => $concepto['clave_unidad'] ?? null,
                'unidad' => $concepto['unidad'] ?? null,
                'valor_unitario' => $valor_unitario,
                'descripcion' => $concepto['descripcion'] ?? null,
                'tipo_impuesto' => $concepto['tipo_impuesto'] ?? null,
                'importe' => $subtotal + $iva ?? 0,
                'obj_imp_id' => $concepto['obj_imp_id'] ?? null
            ]);



            $contador++;
        }

         $name_xml_path = 'CFDI-' . $cfdi->id . '.xml';
        $path_xml = 'emisiones/' . $name_xml_path;
        $ruta = 'cfdi/' . $path_xml;

        $cfdiUpdate = Cfdi::find($cfdi->id);
        $cfdiUpdate->subtotal = $subtotal;
        $cfdiUpdate->impuesto = $iva;
        $cfdiUpdate->total = $total;
        $cfdiUpdate->ruta = $ruta;
        $cfdiUpdate->save();

        // Prepara los datos para el servicio
        $data = [
            'cfdi' => $cfdi,
            'conceptos' => $conceptos
        ];

        // Genera el XML
        $xml = ComplementoXmlService::buildXmlCfdi($data);
        TimbradoService::createCfdiToPDF($cfdiUpdate);

        // Guarda el XML
        Storage::disk('local')->put($path_xml, $xml);
        Storage::disk('public')->put($ruta, $xml);

        // Actualiza el registro con la ruta del XML
        $cfdi->update(['path_xml' => $path_xml]);
        $cfdi->update(['ruta' => $ruta]);
    }

     protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCancelFormAction()
        ];
    }

     protected function getCreateFormAction(): Action
    {
        return Action::make('create')
            ->label('Prefactura')
            ->submit('create')
            ->keyBindings(['mod+s']);
    }


    protected function getCancelFormAction(): Action
    {
        return Action::make('cancel')
            ->label('Cancelar')
            ->color('danger')
            ->icon('heroicon-o-x-mark')
            ->url($this->getRedirectUrl())
            ->requiresConfirmation()
            ->modalHeading('¿Estás seguro?')
            ->modalSubheading('¿Deseas cancelar la creación de este Cfdi?');

    }

}
