<?php

namespace App\Filament\Resources\CfdiResource\Pages;

use Filament\Actions;
use App\Models\Models\CfdiEmisor;
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

        CfdiEmisor::create($data['emisor']);

        // guardar el receptor
        $data['receptor'] = [
            'rfc' => $data['receptor_rfc'],
            'nombre' => $data['receptor_nombre'],
            'domicilio_fiscal' => $data['receptor_domicilio'],
            'regimen_fiscal' => $data['receptor_regimen_fiscal'],
            'uso_cfdi' => $data['receptor_uso_cfdi']
        ];

        CfdiReceptor::create($data['receptor']);

        return $data;
    }

    // Genera el XML después de crear el Cfdi y sus conceptos
    protected function afterCreate(): void
    {
        $cfdi = $this->record; // Modelo Cfdi recién creado

        // Obtén los conceptos relacionados (ajusta el nombre de la relación si es diferente)
        $conceptos = $cfdi->conceptos;

        // Prepara los datos para el servicio
        $data = [
            'cfdi' => $cfdi,
            'conceptos' => $conceptos
        ];

        // Genera el XML
        $xml = ComplementoXmlService::buildXmlCfdi($data);

        // Guarda el XML
        $name_xml_path = 'CFDI-' . $cfdi->id . '.xml';
        $path_xml = 'emisiones/' . $name_xml_path;
        Storage::disk('local')->put($path_xml, $xml);

        // Actualiza el registro con la ruta del XML
        $cfdi->update(['path_xml' => $path_xml]);
    }
}
