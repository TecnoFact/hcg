<?php

namespace App\Http\Controllers;

use App\Models\Emision;
use Illuminate\Http\Request;
use Storage;

class EmisionController extends Controller
{
    public function descargarXmlEmision($emision)
    {
        $emision = Emision::with(['detalles'])->findOrFail($emision)->toArray();

        // construir el xml de la emision tomando en cuenta cfdi 4.0 mexico


        $complementoService = new \App\Services\ComplementoXmlService();
        $xml = $complementoService->buildXmlCfdi($emision);

        $name_xml_path = 'CFDI-' . $emision['id'] . '.xml';
        $path_xml = 'emisiones/' . $name_xml_path;

        Storage::disk('local')->put($path_xml, $xml);

        $pathXmlComplete = Storage::disk('local')->path($path_xml);

        $emisionModel = Emision::findOrFail($emision['id']);
        $emisionModel->path_xml = $path_xml;
        $emisionModel->save();

        return response()->download($pathXmlComplete, $name_xml_path, [
            'Content-Type' => 'application/xml',
        ]);

    }
}
