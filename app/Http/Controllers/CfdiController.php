<?php

namespace App\Http\Controllers;

use App\Models\Emisor;
use App\Models\Models\Cfdi;
use App\Models\Models\CfdiReceptor;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Services\CfdiValidatorService;
use App\Services\CfdiComplementValidatorService;
use App\Services\CfdiCadenaOriginalService;
use App\Services\CfdiSignerService;
use App\Services\CertificadoValidatorService;
use App\Services\CfdiXmlInjectorService;
use App\Services\TimbradoService;
use App\Services\ComplementoXmlService;
use App\Services\AcuseJsonService;
use App\Services\EnvioSatCfdiService;

use Carbon\Carbon;
use Illuminate\Support\Str;
use DOMDocument;

class CfdiController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'xml' => 'required|file|mimes:xml|max:1024',
        ]);

        $file = $request->file('xml');
        $xmlContent = file_get_contents($file);

        // 1. Validar estructura
      /*   $validador = new CfdiValidatorService();
        $validacion = $validador->validate($xmlContent);

        if (!$validacion['valido']) {
            return response()->json([
                'error' => $validacion['error'],
                'detalles' => $validacion['detalle'],
            ], 422);
        }
       */
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
            $receptor = $xml->xpath('//cfdi:Receptor')[0] ?? null;

            $rfcEmisor = $emisor ? (string) $emisor['Rfc'] : null;

            // 3. Validar CSD vs RFC
            $validadorCert = new CertificadoValidatorService();

            //$certificadoPath = storage_path('csd/00001000000708268982.cer');

            $certificadoPath = storage_path('csd/SGS2211257C2/SGS2211257C2.cer');

            $coincide = $validadorCert->validarRfcConCertificado( $certificadoPath, $rfcEmisor);

            if (!$coincide) {
                return response()->json([
                    'error' => 'El RFC del certificado no coincide con el RFC del emisor en el XML.',
                ], 422);
            }

            $keyDerFile = storage_path('csd/SGS2211257C2/SGS2211257C2.key');
            $keyPemFile = $keyDerFile . '.pem';
            $keyPemFileUnprotected = $keyDerFile . '.unprotected.pem';
            $keyDerPass = 'erick1234';

            $openssl = new \CfdiUtils\OpenSSL\OpenSSL();

            // convertir la llave original DER a formato PEM sin contraseña, guardar en $keyPemFileUnprotected
          //  $openssl->derKeyConvert($keyDerFile, $keyDerPass, $keyPemFileUnprotected);

            // 4. Generar cadena original y sello
            $cadenaService = new CfdiCadenaOriginalService();
            $cadenaOriginal = $cadenaService->generar($xmlContent);

            $signer = new CfdiSignerService();
            $firma = $signer->firmarCadena(
                $cadenaOriginal,
                storage_path('csd/SGS2211257C2/SGS2211257C2.key.unprotected.pem'),
                $certificadoPath,
                $keyDerPass
            );

            $sello = $firma['sello'];
            $certificado = $firma['certificado'];
            $noCertificado = $firma['no_certificado'];

            // 5. Insertar sello en XML
            $injector = new CfdiXmlInjectorService();
            $xmlFirmado = $injector->insertarDatosEnXml($xmlContent, $sello, $certificado, $noCertificado);

            // 6. Asegurar atributos de namespace requeridos
            $doc = new DOMDocument();
            $doc->preserveWhiteSpace = false;
            $doc->formatOutput = true;
            $doc->loadXML($xmlFirmado);

            $comprobante = $doc->getElementsByTagName('Comprobante')->item(0);
            $comprobante->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
            $comprobante->setAttribute('xsi:schemaLocation', 'http://www.sat.gob.mx/cfd/4 http://www.sat.gob.mx/sitio_internet/cfd/4/cfdv40.xsd');

            $xmlFirmado = $doc->saveXML();

            // 7. Generar Timbre Fiscal Digital
            $uuid = (string) Str::uuid();
            $timbrador = new TimbradoService();
            $timbreData = $timbrador->generarTimbre([
                'uuid' => $uuid,
                'selloCFD' => $sello
            ], $xmlFirmado, Carbon::parse((string) $xml['Fecha']));

            $complementador = new ComplementoXmlService();
            $xmlFirmado = $complementador->insertarTimbreFiscalDigital($xmlFirmado, $timbreData['xml']);

            // 8. Guardar archivo final
            $nombre = 'cfdis/timbrado_' . $file->getClientOriginalName();
            Storage::disk('local')->put($nombre, $xmlFirmado);

            // 9. Generar Acuse
            $acuseService = new AcuseJsonService();
            $acuse = $acuseService->generarDesdeXml($xmlFirmado);

            // 10. Guardar en base de datos
            $registro = Cfdi::create([
                'user_id' => Auth::id(),
                'nombre_archivo' => $file->getClientOriginalName(),
                'ruta' => $nombre,
                'uuid' => $uuid,
                'sello' => $sello,
                'rfc_emisor' => $rfcEmisor,
                'rfc_receptor' => $receptor ? (string) $receptor['Rfc'] : null,
                'total' => (float) $xml['Total'],
                'fecha' => (string) $xml['Fecha'],
                'tipo_comprobante' => (string) $xml['TipoDeComprobante'],
                'estatus' => 'timbrado',
            ]);

            // 11. Enviar al SAT (SOAP + Blob)
            // app(\App\Services\EnvioSatCfdiService::class)->enviar($registro);

            // 12. Actualizar base de datos despues del envio

            // 13. Enviar al SAT y Azure
           // dd($registro);
            try {
                $envio = new EnvioSatCfdiService();
                $envio->enviar($registro); // Este método ya actualiza los campos necesarios
            } catch (\Exception $e) {
                $registro->update([
                    'respuesta_sat' => 'Error: ' . $e->getMessage(),
                    'intento_envio_sat' => $registro->intento_envio_sat + 1,
                ]);
                \Log::error('Error al enviar CFDI al SAT', [
                    'uuid' => $registro->uuid,
                    'error' => $e->getMessage()
                ]);
            }



            \Log::info('CFDI timbrado exitosamente', [
                'uuid' => $timbreData['uuid'],
                'archivo' => $nombre,
                'cadena original' => $cadenaOriginal
            ]);

            return response()->json([
                'mensaje' => 'CFDI recibido y registrado correctamente',
                'archivo_id' => $registro->id,
                'datos_extraidos' => [
                    'uuid' => $timbreData['uuid'],
                    'emisor_rfc' => $registro->rfc_emisor,
                    'receptor_rfc' => $registro->rfc_receptor,
                    'total' => $registro->total,
                    'fecha' => $registro->fecha,
                    'tipo' => $registro->tipo_comprobante,
                    'cadena_original' => $cadenaOriginal,
                    'sello' => $sello,
                    'certificado' => $certificado,
                    'no_certificado' => $noCertificado,
                    'acuse' => $acuse,
                ],
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

    /**
     * Maneja la subida y envío de un CFDI al SAT.(SOLO XML YA TIMBRADO
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadAndSendSat(Request $request)
    {
        $request->validate([
            'xml' => 'required|file|mimes:xml|max:1024',
        ]);

        $file = $request->file('xml');
        $xmlContent = file_get_contents($file);


        $acuseService = new AcuseJsonService();
        $acuse = $acuseService->generarDesdeXml($xmlContent);

        // 2. Validar complementos
        $xml = simplexml_load_string($xmlContent);

        try {
            // 8. Guardar archivo final
            $nombre = 'cfdis/timbrado_' . $file->getClientOriginalName();
            Storage::disk('local')->put($nombre, $xmlContent);

            $emisor = Emisor::where('rfc', $acuse['rfcEmisor'])->first();
            $receptor = CfdiReceptor::where('rfc', $acuse['rfcReceptor'])->first();

            // 10. Guardar en base de datos
            $registro = Cfdi::create([
                'user_id' => Auth::id(),
                'nombre_archivo' => $file->getClientOriginalName(),
                'emisor_id' => $emisor->id,
                'receptor_id' => $receptor->id,
                'ruta' => $nombre,
                'tipo_de_comprobante' => (string) $xml['TipoDeComprobante'],
                'uuid' => $acuse['uuid'],
                'sello' => $xml['Sello'],
                'rfc_emisor' => $acuse['rfcEmisor'],
                'rfc_receptor' => $acuse['rfcReceptor'] ? (string) $acuse['rfcReceptor'] : null,
                'total' => (float) $xml['Total'],
                'fecha' => (string) $xml['Fecha'],
                'estatus' => 'timbrado',
                'moneda' => (string) $xml['Moneda'],
                'subtotal' => (float) $xml['SubTotal'],
                'exportacion' => (string) $xml['Exportacion'],
                'lugar_expedicion' => (string) $xml['LugarExpedicion'],
                'intento_envio_sat' => 1
            ]);

            // Enviar al SAT y Azure
            try {
                $envio = new EnvioSatCfdiService();
                $envio->onlyUploadAndSendSat($xmlContent, $acuse['uuid']); // Este método ya actualiza los campos necesarios
            } catch (\Exception $e) {
                $registro->update([
                    'respuesta_sat' => 'Error: ' . $e->getMessage(),
                    'intento_envio_sat' => $registro->intento_envio_sat + 1,
                ]);
                \Log::error('Error al enviar CFDI al SAT', [
                    'uuid' => $registro->uuid,
                    'error' => $e->getMessage()
                ]);
            }


            return response()->json([
                'mensaje' => 'CFDI recibido y registrado correctamente',
                'archivo_id' => $registro->id,
                'datos_extraidos' => [
                    'uuid' => $acuse['uuid'],
                    'emisor_rfc' => $registro->emisor->rfc,
                    'receptor_rfc' => $registro->receptor->rfc,
                    'total' => $registro->total,
                    'fecha' => $registro->fecha,
                    'tipo' => $registro->tipo_de_comprobante,
                    //'cadena_original' => $cadenaOriginal,
                    'sello' => $xml['Sello'],
                    'certificado' => $xml['Certificado'],
                    'no_certificado' => $xml['NoCertificado'],
                    'acuse' => $acuse,
                ],
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

    public function descargarCfdiXml($factura)
    {
        $cfdi = Cfdi::find($factura);
        if (!$cfdi) {
            Notification::make()
                ->title('Error al descargar XML')
                ->danger()
                ->body('No se encontró el CFDI con el ID proporcionado.')
                ->send();

            return redirect()->back();
        }

        $ruta = $cfdi->path_xml;

        if (empty($ruta)) {
            Notification::make()
                ->title('Error al descargar XML')
                ->danger()
                ->body('Error al obtener la ruta del archivo.')
                ->send();

            return redirect()->back();
        }

        $path = Storage::disk('local')->path($ruta);

        if (!file_exists($path)) {
            Notification::make()
                ->title('Error al descargar XML')
                ->danger()
                ->body('El archivo XML no se encuentra en el servidor.')
                ->send();

            return redirect()->back();
        }

         Notification::make()
            ->title('Descarga exitosa')
            ->success()
            ->body('El archivo XML se ha descargado correctamente.')
            ->send();

        return response()->download($path, basename($ruta), [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function descargarCfdiPdf($factura)
    {
        $factura = Cfdi::find($factura);

        if (!$factura) {
            Notification::make()
                ->title('Error al descargar PDF')
                ->danger()
                ->body('No se encontró el CFDI con el ID proporcionado.')
                ->send();

            return redirect()->back();
        }

        if($factura->status_upload !== 'timbrado') {
            TimbradoService::createCfdiSimpleToPDF($factura);
        }else{
            TimbradoService::createCfdiToPDF($factura);
        }

        if ($factura->pdf_path === null) {
            Notification::make()
                ->title('error al generar PDF')
                ->danger()
                 ->body('El archivo PDF no existe.')
                 ->send();

        }

        if(Storage::disk('public')->exists($factura->pdf_path)) {
            return response()->download(
                Storage::disk('public')->path($factura->pdf_path),
            null,
            [
                'Content-Type' => 'application/pdf',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        }
    }

    public function descargarXml($factura)
    {
        $cfdi = Cfdi::find($factura);
        $ruta = $cfdi->ruta;

        if (empty($ruta)) {
            Notification::make()
                ->title('Error al descargar XML')
                ->danger()
                ->body('Error al obtener la ruta del archivo.')
                ->send();

            return redirect()->back();
        }

        $path = Storage::disk('public')->path($ruta);

        if (!file_exists($path)) {
              Notification::make()
                ->title('Error al descargar XML')
                ->danger()
                ->body('Error al obtener la ruta del archivo.')
                ->send();

            return redirect()->back();
        }

         Notification::make()
            ->title('Descarga exitosa')
            ->success()
            ->body('El archivo XML se ha descargado correctamente.')
            ->send();



        return response()->download($path, basename($ruta), [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function descargarPdf($factura)
    {
        $cfdi = Cfdi::find($factura);
        $cfdi->pdf_path = null;
        $cfdi->save();

        TimbradoService::createCfdiToPDF($cfdi);

        $cfdiPathPdf = Cfdi::find($factura);
        if (!$cfdiPathPdf) {
            Notification::make()
                ->title('Error al descargar PDF')
                ->danger()
                ->body('No se encontró el CFDI con el ID proporcionado.')
                ->send();

            return redirect()->back();
        }

        $pdfPath = $cfdiPathPdf->pdf_path;

        if (empty($pdfPath)) {
            Notification::make()
                ->title('Error al descargar PDF')
                ->danger()
                ->body('El archivo PDF no está disponible.')
                ->send();

            return redirect()->back();
        }

        $path = Storage::disk('public')->path($pdfPath);

        if (!file_exists($path)) {
            Notification::make()
                ->title('Error al descargar PDF')
                ->danger()
                ->body('El archivo PDF no se encuentra en el servidor.')
                ->send();

            return redirect()->back();
        }

        Notification::make()
            ->title('Descarga exitosa')
            ->success()
            ->body('El archivo PDF se ha descargado correctamente.')
            ->send();

        return response()->download($path, basename($pdfPath), [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function cancelCfdi($factura)
    {
        $cfdi = Cfdi::find($factura);

        if (!$cfdi) {
            Notification::make()
                ->title('Error al cancelar CFDI')
                ->danger()
                ->body('No se encontró el CFDI con el ID proporcionado.')
                ->send();

            return redirect()->back();
        }

        $fileXml = Storage::disk('public')->path($cfdi->ruta);

        if (!file_exists($fileXml)) {
            Notification::make()
                ->title('Error al cancelar CFDI')
                ->danger()
                ->body('El archivo XML del CFDI no se encuentra en el servidor.')
                ->send();

            return redirect()->back();
        }



        $xmlContent = file_get_contents($fileXml);
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xmlContent);

        // Eliminar nodo TimbreFiscalDigital dentro de Complemento
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');
        $xpath->registerNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');

        // Eliminar el nodo TimbreFiscalDigital si existe
        foreach ($xpath->query('//cfdi:Complemento/tfd:TimbreFiscalDigital') as $tfdNode) {
            $tfdNode->parentNode->removeChild($tfdNode);
        }

        // Eliminar atributos del nodo Comprobante
        $comprobanteNodes = $dom->getElementsByTagName('Comprobante');
        if ($comprobanteNodes->length > 0) {
            $comprobante = $comprobanteNodes->item(0);
            $comprobante->removeAttribute('NoCertificado');
            $comprobante->removeAttribute('Certificado');
            $comprobante->removeAttribute('Sello');
        }

        $dom->save($fileXml);

         $pathXml = Storage::disk('local')->path($cfdi->path_xml);

        if (!file_exists($pathXml)) {
            Notification::make()
                ->title('Error al cancelar CFDI')
                ->danger()
                ->body('El archivo XML del CFDI no se encuentra en el servidor.')
                ->send();

            return redirect()->back();
        }



        $xmlContent = file_get_contents($pathXml);
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xmlContent);

        // Eliminar nodo TimbreFiscalDigital dentro de Complemento
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');
        $xpath->registerNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');

        // Eliminar el nodo TimbreFiscalDigital si existe
        foreach ($xpath->query('//cfdi:Complemento/tfd:TimbreFiscalDigital') as $tfdNode) {
            $tfdNode->parentNode->removeChild($tfdNode);
        }

        // Eliminar atributos del nodo Comprobante
        $comprobanteNodes = $dom->getElementsByTagName('Comprobante');
        if ($comprobanteNodes->length > 0) {
            $comprobante = $comprobanteNodes->item(0);
            $comprobante->removeAttribute('NoCertificado');
            $comprobante->removeAttribute('Certificado');
            $comprobante->removeAttribute('Sello');
        }

        $dom->save($pathXml);

        $cfdi->sello = '';
        $cfdi->uuid = '';
        $cfdi->status_upload = Cfdi::ESTATUS_SUBIDO;
        $cfdi->save();

        Notification::make()
            ->title('CFDI cancelado')
            ->success()
            ->body('El CFDI ha sido cancelado correctamente.')
            ->send();

        return redirect()->back();
        //return redirect()->route('filament.admin.resources.cfdis.edit', $cfdi);
    }
}

