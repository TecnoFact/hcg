<?php

namespace App\Http\Controllers;

use App\Services\CfdiCadenaOriginalService;
use App\Services\CfdiComplementValidatorService;
use App\Services\CfdiSignerService;
use App\Services\TimbradoService;
use DOMDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TimbradoController extends Controller
{
    /**
     * Simula el timbrado de un CFDI firmado.
     */
    public function timbrar(Request $request)
    {
        \Log::info('Entró al método timbrar');
        try {
            $request->validate([
                'xml' => 'required|file|mimes:xml|max:1024',
            ]);
            \Log::info('Validación de archivo pasó correctamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Error de validación', ['errors' => $e->errors()]);
            return response()->json([
                'status' => 'error',
                'mensaje' => 'La validación del archivo falló.',
                'errores' => $e->errors(),
            ], 422);
        }

        $archivo = $request->file('xml');
        $xmlContent = file_get_contents($archivo);
        // Simular UUID y fecha de timbrado
        $uuid = Str::uuid()->toString();
        $fechaTimbrado = now()->toIso8601String();

        // Agregar un timbre simulado como complemento (solo para fines de prueba)
        $timbre = <<<XML
        <cfdi:Complemento>
            <tfd:TimbreFiscalDigital xmlns:tfd="http://www.sat.gob.mx/TimbreFiscalDigital"
                Version="1.1"
                UUID="{$uuid}"
                FechaTimbrado="{$fechaTimbrado}"
                RfcProvCertif="EKU9003173C9"
                SelloCFD="..."
                NoCertificadoSAT="00001000000403258748"
                SelloSAT="..." />
        </cfdi:Complemento>
        XML;
        \Log::info('Inserto timbre fiscal');
        // Insertar timbre al final antes del cierre del XML
        $xmlTimbrado = preg_replace(
            '/<\/cfdi:Comprobante>/',
            $timbre . "
                </cfdi:Comprobante>",
            $xmlContent
        );

        // Guardar XML timbrado
        $nombre = 'cfdis/timbrado_' . $archivo->getClientOriginalName();
        Storage::disk('local')->put($nombre, $xmlTimbrado);
        \Log::info('CFDI timbrado exitosamente', [
            'uuid' => $uuid,
            'archivo' => $nombre
        ]);
        return response()->json([
            'status' => 'success',
            'mensaje' => 'El CFDI fue timbrado exitosamente.',
            'uuid' => $uuid,
            'fecha_timbrado' => $fechaTimbrado,
            'archivo_guardado' => $nombre
        ]);

    }





    /**
     * Funcion para sellar unu xml usando certificado, key, password, y el xml vienen desde el servicio
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function sellarCfdi(Request $request)
    {


        $request->validate([
            'xml' => 'required|file|mimes:xml|max:1024',
            'certificado' => 'required|file|max:1024',
            'key' => 'required|file|max:1024',
            'password' => 'required|string|max:255',
        ]);

        $file = $request->file('xml');

        $xmlContent = file_get_contents($file);

        $pathCertificado = 'cfdis/certificados/' . $request->file('certificado')->getClientOriginalName();
        $pathKey = 'cfdis/keys/' . $request->file('key')->getClientOriginalName();

        // quiero almacenar usando Storage el xml, certificado, key en el disk local
        Storage::disk('local')->put('cfdis/original/' . $file->getClientOriginalName(), $xmlContent);
        Storage::disk('local')->put($pathCertificado, $request->file('certificado')->get());
        Storage::disk('local')->put($pathKey, $request->file('key')->get());

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

            $emisorData = [
                'certificado' => $pathCertificado,
                'key' => $pathKey,
                'password' => $request->input('password'),
                'rfc' => $emisor['Rfc'],
            ];


            $processXml = TimbradoService::sellarCfdiWithoutEmisor($xmlContent, $emisorData);


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
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
