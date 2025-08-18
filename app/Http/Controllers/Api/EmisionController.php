<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Emisor;
use App\Models\Models\Cfdi;
use App\Models\Models\CfdiReceptor;
use App\Services\AcuseJsonService;
use App\Services\CertificadoValidatorService;
use App\Services\CfdiCadenaOriginalService;
use App\Services\CfdiComplementValidatorService;
use App\Services\CfdiSignerService;
use App\Services\CfdiXmlInjectorService;
use App\Services\ComplementoXmlService;
use App\Services\TimbradoService;
use Carbon\Carbon;
use CfdiUtils\Certificado\Certificado;
use DOMDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Notification;

class EmisionController extends Controller
{

    /**
     * Summary of generateSealFromXml
     * Function para generar sellado del XML
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateSealFromXml(Request $request)
    {
        $request->validate([
            'xml' => 'required|file|mimes:xml|max:1024',
        ]);

        $file = $request->file('xml');

        $xmlContent = file_get_contents($file);


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
            $emisorFind = Emisor::where('rfc', $rfcEmisor)->first();


            if(!$emisorFind)
            {
                return response()->json(['error' => "El emisor $rfcEmisor no existe registrado"], 422);
            }

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



    /**
     * Summary of stampCfdiFromXml Timbrado de XML
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stampCfdiFromXml(Request $request)
    {

         $request->validate([
            'xml' => 'required|file|mimes:xml|max:1024',
        ]);

        $file = $request->file('xml');
        $xmlContent = file_get_contents($file);

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

        // obtener el sello desde el xml $xmlContent
        $sello = (string) $xml->xpath('//cfdi:Comprobante/@Sello')[0] ?? null;


        try {
          // 7. Generar Timbre Fiscal Digital
            $uuid = (string) Str::uuid();
            $timbrador = new TimbradoService();
            $timbreData = $timbrador->generarTimbre([
                'uuid' => $uuid,
                'selloCFD' => $sello
            ], $xmlContent, Carbon::parse((string) $xml['Fecha']));

            $complementador = new ComplementoXmlService();
            $xmlTimbrado = $complementador->insertarTimbreFiscalDigital($xmlContent, $timbreData['xml']);

            // 8. Guardar archivo final
            $nombre = 'cfdis/timbrado_' . $file->getClientOriginalName();
            Storage::disk('local')->put($nombre, $xmlTimbrado);

            $namespaces = $xml->getNamespaces(true);
            $xml->registerXPathNamespace('cfdi', $namespaces['cfdi'] ?? '');
            $emisor = $xml->xpath('//cfdi:Emisor')[0] ?? null;



            if(!$emisor)
            {
                return response()->json(['error' => 'El emisor no está presente en el XML.'], 422);
            }

            $rfcEmisor = $emisor ? (string) $emisor['Rfc'] : null;

            $emisor = Emisor::where('rfc', $rfcEmisor)->first();

            if(!$emisor)
            {
                return response()->json(['error' => "El emisor $rfcEmisor no existe registrado"], 422);
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
