<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Emisor;
use App\Services\CfdiComplementValidatorService;
use App\Services\ComplementoXmlService;
use App\Services\TimbradoService;
use Carbon\Carbon;
use DOMDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmisionController extends Controller
{

    /**
     * Summary of generateSealFromXml (DEPRECATED)
     * Function para generar sellado del XML
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
/*
    public function generateSealFromXml(Request $request)
    {
       $request->validate([
            'xml' => 'required|string',
        ]);

        // 1. Decodificar base64 (asegúrate de que no venga con encabezado tipo data:)
        $xmlBase64 = $request->input('xml');
        if (str_starts_with($xmlBase64, 'data:')) {
            $xmlBase64 = substr($xmlBase64, strpos($xmlBase64, ',') + 1);
        }

        $xmlContent = base64_decode($xmlBase64, true);


        // 2. Validar complementos
        $xml = simplexml_load_string($xmlContent);
        $complementValidator = new CfdiComplementValidatorService();
        $complementValidation = $complementValidator->validateComplements($xml);

        if (!$complementValidation['valido']) {
            return response()->json([
                'error' => $complementValidation['error'],
                'detalles' => $complementValidation['detalles'] ?? null,
            ], 422);
        }

        try {
            $namespaces = $xml->getNamespaces(true);
            $xml->registerXPathNamespace('cfdi', $namespaces['cfdi'] ?? '');
            $emisor = $xml->xpath('//cfdi:Emisor')[0] ?? null;


            if(!$emisor)
            {
                return response()->json(['error' => 'El emisor no está presente en el XML.'], 422);
            }

            $rfcEmisor = $emisor ? (string) $emisor['Rfc'] : null;


            $processXml = TimbradoService::sellarCfdi($xmlContent, $emisorFind);


            $xmlFirmado = $processXml['xml'];

            $doc = new DOMDocument();
            $doc->preserveWhiteSpace = false;
            $doc->formatOutput = true;
            $doc->loadXML($xmlFirmado);

            $comprobante = $doc->getElementsByTagName('Comprobante')->item(0);
            $comprobante->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
            $comprobante->setAttribute('xsi:schemaLocation', 'http://www.sat.gob.mx/cfd/4 http://www.sat.gob.mx/sitio_internet/cfd/4/cfdv40.xsd');

            $xmlFirmado = $doc->saveXML();

            // generar el base64
            $base64 = base64_encode($xmlFirmado);

            // hasta aqui debe llegar
            return response()->json(['mensaje' => 'CFDI sellado correctamente', 'xml' => $base64]);

         } catch (\Exception $e) {

            Log::error('Error al procesar CFDI: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());

            return response()->json([
                'error' => 'Error interno',
            ], 500);
        }
    }
*/


    /**
     * Summary of stampCfdiFromXml Timbrado de XML
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stampCfdiFromXml(Request $request)
    {

        $request->validate([
            'xml' => 'required|string',
        ]);

        // 1. Decodificar base64 (asegúrate de que no venga con encabezado tipo data:)
        $xmlBase64 = $request->input('xml');
        if (str_starts_with($xmlBase64, 'data:')) {
            $xmlBase64 = substr($xmlBase64, strpos($xmlBase64, ',') + 1);
        }

        $xmlContent = base64_decode($xmlBase64, true);

        if ($xmlContent === false) {
            return response()->json(['error' => 'XML inválido, no se pudo decodificar base64'], 400);
        }

        // agregar el contenido en un archivo xml
        $nameFileXml = 'cfdis/original_' . Str::uuid() . '.xml';
        Storage::disk('local')->put($nameFileXml, $xmlContent);
        $pathXml = Storage::disk('local')->path($nameFileXml);

        $xmlContent = Storage::disk('local')->get($nameFileXml);


        // 2. Validar que realmente sea XML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);

        if ($xml === false) {
            $errors = libxml_get_errors();
            return response()->json(['error' => 'XML mal formado', 'details' => $errors], 400);
        }


        $sello = isset($xml['Sello']) ? (string)$xml['Sello'] : null;
        $noCertificado = isset($xml['NoCertificado']) ? (string)$xml['NoCertificado'] : null;
        $certificado = isset($xml['Certificado']) ? (string)$xml['Certificado'] : null;

        $faltantes = [];
        if (empty($sello)) $faltantes[] = 'Sello';
        if (empty($noCertificado)) $faltantes[] = 'NoCertificado';
        if (empty($certificado)) $faltantes[] = 'Certificado';

        if (!empty($faltantes)) {
            return response()->json([
                'error' => 'El XML no contiene: ' . implode(', ', $faltantes)
            ], 422);
        }

        // 3. Pasar a tu validador de complementos
        $complementValidator = new CfdiComplementValidatorService();
        $complementValidation = $complementValidator->validateComplements($xml);

        if (!$complementValidation['valido']) {
            return response()->json([
                'error' => $complementValidation['error'],
                'detalles' => $complementValidation['detalles'] ?? null,
            ], 422);
        }

        try {
          // 7. Generar Timbre Fiscal Digital
            $uuid = (string) Str::uuid();
            $timbrador = new TimbradoService();
            $timbreData = $timbrador->generarTimbre([
                'uuid' => $uuid,
                'selloCFD' => $sello
            ], $xmlContent, Carbon::parse((string) $xml['Fecha']));

            $complementador = new ComplementoXmlService();
            $xmlTimbrado = $complementador->insertarTimbreFiscalDigital($pathXml, $timbreData['xml']);


            // 8. Guardar archivo final
            $nombre = 'cfdis/timbrado_' . $uuid . '.xml';
            Storage::disk('local')->put($nombre, $xmlTimbrado);

            $namespaces = $xml->getNamespaces(true);
            $xml->registerXPathNamespace('cfdi', $namespaces['cfdi'] ?? '');
            $emisor = $xml->xpath('//cfdi:Emisor')[0] ?? null;

            if(!$emisor)
            {
                return response()->json(['error' => 'El emisor no está presente en el XML.'], 422);
            }

            // base64
            $xmlTimbrado = base64_encode($xmlTimbrado);

            \Log::info('CFDI timbrado exitosamente', [
                'uuid' => $timbreData['uuid'],
                'archivo' => $nombre,
            ]);

            return response()->json([
                'mensaje' => 'CFDI timbrado exitosamente',
                'xml' => $xmlTimbrado,
                'uuid' => $timbreData['uuid']
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error interno',
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine()
            ], 500);
        }
    }
}
