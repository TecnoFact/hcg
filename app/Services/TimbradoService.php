<?php

namespace App\Services;

use App\Models\CfdiArchivo;
use App\Models\Emisor;
use Auth;
use Carbon\Carbon;
use CfdiUtils\Cfdi;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use DOMDocument;
use Exception;
use CfdiUtils\TimbreFiscalDigital\TfdCadenaDeOrigen;

class TimbradoService
{
    public function generarTimbre(array $datosCfdi, string $cfdiXml, Carbon $fechaSimulada): array
    {
        $hsmUrl = 'http://35.208.215.143/akval-firma/api/FirmaHsm/FirmaCxi';
        $certificadoSAT = '00001000000710051653';
        $rfcPAC = 'ASF180914KY5';

        $uuid = $datosCfdi['uuid'];
        $fechaTimbrado = $fechaSimulada->format('Y-m-d\TH:i:s');

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = false;
        $doc->preserveWhiteSpace = false;

        // Crear nodo TFD
        $tfd = $doc->createElementNS('http://www.sat.gob.mx/TimbreFiscalDigital', 'tfd:TimbreFiscalDigital');

        // Agregar atributos obligatorios
        $tfd->setAttribute('Version', '1.1');
        $tfd->setAttribute('UUID', $uuid);
        $tfd->setAttribute('FechaTimbrado', $fechaTimbrado);
        $tfd->setAttribute('SelloCFD', $datosCfdi['selloCFD']);
        $tfd->setAttribute('NoCertificadoSAT', $certificadoSAT);
        $tfd->setAttribute('RfcProvCertif', $rfcPAC);

        // ✅ Agregar namespace xsi explícito
        $tfd->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:xsi',
            'http://www.w3.org/2001/XMLSchema-instance'
        );

        // ✅ Agregar el schemaLocation obligatorio
        $tfd->setAttributeNS(
            'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation',
            'http://www.sat.gob.mx/TimbreFiscalDigital http://www.sat.gob.mx/sitio_internet/cfd/TimbreFiscalDigital/TimbreFiscalDigitalv11.xsd'
        );

        $doc->appendChild($tfd);

        // Esto es lo que se firma y posteriormente se inserta sin cambios
        $tfdXml = $doc->saveXML($tfd);

        // Generar cadena original del TFD
        $builder = new TfdCadenaDeOrigen();
        $cadenaOriginalTFD = $builder->build($tfdXml);

        Log::debug('Cadena original del Timbre generada', ['cadena' => $cadenaOriginalTFD]);

        $hash = hash('sha256', $cadenaOriginalTFD, true);
        $hashBase64 = base64_encode($hash);

        Log::debug('Hash generado', [
            'hash_hex' => bin2hex($hash),
            'hash_base64' => $hashBase64
        ]);

        // Obtener sello desde HSM sin regenerar nodo
        $selloSAT = $this->firmarConHSM($hsmUrl, $hashBase64);
        $this->validarSelloSAT($selloSAT, $cadenaOriginalTFD, $rfcPAC);

        // Agregar sello SAT al nodo ya firmado
        $tfd->setAttribute('SelloSAT', $selloSAT);

        return [
            'uuid' => $uuid,
            'xml' => $doc->saveXML($tfd),
        ];
    }

    static function firmarConHSM(string $url, string $hash): string
    {
        $ch = curl_init($url . '?hash=' . urlencode($hash));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || empty($response)) {
            Log::error('Error en comunicación con HSM', [
                'http_code' => $httpCode,
                'error' => $error,
                'hash_enviado' => $hash
            ]);
            throw new Exception("Error al firmar con HSM: $error");
        }

        return $response;
    }

    static function firmarConHSMOther(string $url, string $hash): string
    {
       $ch = curl_init($url . '?hash=' . $hash);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || empty($response)) {
            Log::error('Error en comunicación con HSM', [
                'http_code' => $httpCode,
                'error' => $error,
                'hash_enviado' => $hash
            ]);
            throw new Exception("Error al firmar con HSM: $error");
        }


        return $response;
    }

    private function validarSelloSAT(string $selloSAT, string $cadenaOriginal, string $rfcPAC): void
    {
        $certPath = storage_path("app/certs/{$rfcPAC}.cer");
        $certContent = file_get_contents($certPath);

        if (strpos($certContent, '-----BEGIN CERTIFICATE-----') === false) {
            $certContent = "-----BEGIN CERTIFICATE-----\n" .
                chunk_split(base64_encode($certContent), 64, "\n") .
                "-----END CERTIFICATE-----\n";
        }

        $pubKey = openssl_pkey_get_public($certContent);
        if (!$pubKey) {
            throw new Exception("Error al cargar clave pública: " . openssl_error_string());
        }

        $result = openssl_verify(
            $cadenaOriginal,
            base64_decode($selloSAT),
            $pubKey,
            'sha256WithRSAEncryption'
        );

        if ($result !== 1) {
            throw new Exception("Validación de sello fallida: " . openssl_error_string());
        }
    }


    /**
     * Método para timbrar un CFDI desde un archivo XML.
     *
     * @param string $xml Ruta del archivo XML a timbrar.
     * @param Emisor $emisor Objeto Emisor con los datos del emisor.
     * @return \Illuminate\Http\JsonResponse
     */
    static function timbraSellarXml($xml, Emisor $emisor)
    {
         $file = $xml;
         $registro = null;
         $xmlContent = file_get_contents($file);


        // 2. Validar complementos
        $xml = simplexml_load_string($xmlContent);
        $complementValidator = new CfdiComplementValidatorService();
        $complementValidation = $complementValidator->validateComplements($xml);

        if (!$complementValidation['valido']) {
            $errorMsg = $complementValidation['error'] ?? 'Error de complemento';
            $detalles = $complementValidation['detalles'] ?? null;
            $msg = $errorMsg . ($detalles ? (': ' . json_encode($detalles)) : '');
            throw new \Exception($msg, 422);
        }

        try {
            $namespaces = $xml->getNamespaces(true);
            $xml->registerXPathNamespace('cfdi', $namespaces['cfdi'] ?? '');
            $emisorXml = $xml->xpath('//cfdi:Emisor')[0] ?? null;
            $receptor = $xml->xpath('//cfdi:Receptor')[0] ?? null;

            $rfcEmisor = $emisorXml ? (string) $emisorXml['Rfc'] : null;

            // 3. Validar CSD vs RFC
            $validadorCert = new CertificadoValidatorService();

            $certificateFromEditor = Storage::disk('local')->path($emisor->file_certificate);

            $keyFromEditor = Storage::disk('local')->path($emisor->file_key);
           // dd($certificateFromEditor, $keyFromEditor);

            if( !file_exists($certificateFromEditor) || !file_exists($keyFromEditor)) {
                throw new \Exception('Los archivos de certificado o llave no existen.', 422);
            }

            $keyDerFile = $keyFromEditor;
            $keyPemFile = $keyDerFile . '.pem';
            $keyPemFileUnprotected = $keyDerFile . '.unprotected.pem';
            $keyDerPass = $emisor->password_key;

            $openssl = new \CfdiUtils\OpenSSL\OpenSSL();
          //  dd($keyDerFile, $keyPemFileUnprotected, $keyDerPass);
            if( !file_exists($keyPemFileUnprotected)) {
                // Convertir clave DER a PEM
                 $openssl->derKeyConvert($keyDerFile, $keyDerPass, $keyPemFileUnprotected);
            }


            $fileKeyPem = $keyPemFileUnprotected;


           // $coincide = $validadorCert->validarRfcConCertificado(storage_path('csd/00001000000708268982.cer'), $rfcEmisor);
            $coincide = $validadorCert->validarRfcConCertificado($certificateFromEditor, $rfcEmisor);

            if (!$coincide) {
               throw new \Exception('El RFC del emisor no coincide con el certificado CSD.', 422);
            }

            // 4. Generar cadena original y sello
            $cadenaService = new CfdiCadenaOriginalService();
            $cadenaOriginal = $cadenaService->generar($xmlContent);


            $signer = new CfdiSignerService();
            $firma = $signer->firmarCadena(
                $cadenaOriginal,
                $keyPemFileUnprotected,
                $certificateFromEditor,
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

            //$complementador = new ComplementoXmlService();
            //$xmlFirmado = $complementador->insertarTimbreFiscalDigital($xmlFirmado, $timbreData['xml']);

            // 8. Guardar archivo final
            $nombre = 'cfdis/timbrado_' .$emisor->rfc. '_'. $uuid .'.xml';
            Storage::disk('local')->put($nombre, $xmlFirmado);

            // copy file to public folder
            Storage::disk('public')->put($nombre, $xmlFirmado);
            $ruta = $nombre;



            // 9. Generar Acuse
            $acuseService = new AcuseJsonService();
            $acuse = $acuseService->generarDesdeXml($xmlFirmado);

            // 10. Guardar en base de datos
            $registro = CfdiArchivo::create([
                'user_id' => Auth::id(),
                'nombre_archivo' => $nombre,
                'ruta' => $ruta,
                'uuid' => $uuid,
                'sello' => $sello,
                'rfc_emisor' => $rfcEmisor,
                'rfc_receptor' => $receptor ? (string) $receptor['Rfc'] : null,
                'total' => (float) $xml['Total'],
                'fecha' => (string) $xml['Fecha'],
                'tipo_comprobante' => (string) $xml['TipoDeComprobante'],
                'estatus' => 'timbrado',
            ]);


            \Log::info('CFDI timbrado exitosamente', [
                'uuid' => $timbreData['uuid'],
                'archivo' => $nombre,
                'cadena original' => $cadenaOriginal
            ]);

            return $registro;

        } catch (\Exception $e) {

            Log::error('Error al procesar el CFDI', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'files' => $file,
                'emisor_rfc' => $emisor->rfc,
            ]);

            // Si ocurre un error, se puede lanzar una excepción o retornar un error
           throw new \Exception('Error al procesar el CFDI: ' . $e->getMessage(), 500);
        }
    }


    static function envioxml($registro)
    {

            $data = ['success' => false, 'message' => ''];

            try {
                $envio = new EnvioSatCfdiService();
                $envio->enviarXml($registro); // Este método ya actualiza los campos necesarios
                $data['success'] = true;
                $data['message'] = 'CFDI enviado al SAT correctamente';

                return $data;
            } catch (\Exception $e) {
                $registro->update([
                    'respuesta_sat' => 'Error: ' . $e->getMessage(),
                    'intento_envio_sat' => $registro->intento_envio_sat + 1,
                ]);
                \Log::error('Error al enviar CFDI al SAT', [
                    'uuid' => $registro->uuid,
                    'error' => $e->getMessage()
                ]);

                $data['message'] = 'Error al enviar CFDI al SAT: ' . $e->getMessage();
                $data['success'] = false;

                throw new \Exception('Error al enviar CFDI al SAT: ' . $e->getMessage(), 500);
            }
    }

    static function generatePdfFromXml($xmlsat)
    {
        // Aquí puedes implementar la lógica para generar un PDF a partir del XML
        // Por ejemplo, usando una librería como Snappy o Dompdf

        $xmlResponse = simplexml_load_string($xmlsat);

         if ($xmlResponse === false) {
            throw new Exception("Respuesta XML inválida del SAT");
        }

        // Registrar respuesta completa para depuración
        Log::debug('Respuesta completa del SAT', ['response' => $xmlsat]);

        // Registrar namespaces encontrados
        $namespaces = $xmlResponse->getNamespaces(true);
        Log::debug('Namespaces en la respuesta', ['namespaces' => $namespaces]);

        // Registrar estructura XML para diagnóstico
        Log::debug('Estructura XML recibida', ['xml' => $xmlResponse->asXML()]);

        // Registrar el cuerpo del SOAP
        $body = $xmlResponse->children('s', true)->Body;
        if (!$body) {
            throw new Exception("No se encontró el elemento Body en la respuesta SOAP");
        }

        // Registrar contenido del Body
        Log::debug('Contenido del Body SOAP', ['body' => $body->asXML()]);

        // Acceder al nodo AcuseRecepcionCFDI
        $acuse = $body->children('http://recibecfdi.sat.gob.mx')->AcuseRecepcion->AcuseRecepcionCFDI;

        // Extraer los datos del acuse
        $uuid = (string)$acuse->attributes()['UUID'];
        $codigo = (string)$acuse->attributes()['CodEstatus'];
        $fechaAcuse = (string)$acuse->attributes()['Fecha'];
        $noCertificadoSAT = (string)($acuse->attributes()['NoCertificadoSAT'] ?? '');

        // Extraer datos de incidencia si existen
        $incidencia = $acuse->Incidencia;
        $incidenciaData = null;
        if ($incidencia) {
            $incidenciaData = [
                'mensaje' => (string)($incidencia->MensajeIncidencia ?? ''),
                'codigo_error' => (string)($incidencia->CodigoError ?? ''),
                'rfc_emisor' => (string)($incidencia->RfcEmisor ?? ''),
                'id_incidencia' => (string)($incidencia->IdIncidencia ?? ''),
                'fecha_registro' => (string)($incidencia->FechaRegistro ?? '')
            ];
        }

        // Usar el wrapper de Laravel para DomPDF
        $pdfPath = storage_path('app/public/acuse_cfdi_' . $uuid . '.pdf');
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('template.pdf-acuse', [
            'uuid' => $uuid,
            'codigo' => $codigo,
            'fechaAcuse' => Carbon::parse($fechaAcuse)->format('Y-m-d H:i:s'),
            'noCertificadoSAT' => $noCertificadoSAT,
            'incidenciaData' => $incidenciaData
        ]);
        $pdf->setPaper('A4', 'portrait');
        file_put_contents($pdfPath, $pdf->output());
        Log::info('PDF generado correctamente', ['pdf_path' => $pdfPath]);

        return $pdfPath;
    }
}
