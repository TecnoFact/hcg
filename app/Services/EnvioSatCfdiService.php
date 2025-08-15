<?php

namespace App\Services;

use App\Models\Emisor;
use App\Models\Models\Cfdi;
use Carbon\Carbon;
use DOMDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\CfdiArchivo;
use GuzzleHttp\Client;
use Exception;
use PhpCfdi\Credentials\Certificate;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\Fiel;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\FielRequestBuilder;
use PhpCfdi\SatWsDescargaMasiva\Service;
use PhpCfdi\SatWsDescargaMasiva\WebClient\GuzzleWebClient;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use Str;

class EnvioSatCfdiService
{
    public function enviar(Cfdi $cfdi): void
    {
        $xml = Storage::disk('local')->get($cfdi->ruta);
        $nameXml = $cfdi->uuid . '.xml';

        Log::info('Iniciando proceso de envío SAT para CFDI', ['uuid' => $cfdi->uuid]);

       // $rfc = config('pac.Rfc');
        $rfc = $cfdi->emisor->rfc;

        $fecha = Carbon::parse($cfdi->fecha)->format('Y-m-d\TH:i:s');
        $cadena = "||{$rfc}|{$fecha}||";

        $cadena = base64_encode(hash('sha256', $cadena, true));

        $sello = $this->firmarCadenaConHsmPkcs7($cadena);
        Log::debug('Sello generado', ['sello' => $sello]);

        $token = $this->autenticarseEnSat();
        Log::debug('Token recibido del SAT', ['token' => $token]);

        $this->subirABlob($cfdi->uuid, $xml);
        Log::info('CFDI almacenado en Azure Blob', ['uuid' => $cfdi->uuid]);

        $this->enviarSoapSat($token, $cfdi, $nameXml);
        Log::info('CFDI enviado exitosamente al SAT', ['uuid' => $cfdi->uuid]);


    }

    static function firmarCadenaConHsmPkcs7(string $cadena): string
    {
        $url = 'http://35.208.215.143/akval-firma/api/FirmaHsm/FirmaCxi';

        $ch = curl_init($url . '?hash=' . urlencode($cadena));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || empty($response)) {
            Log::error('Error en firma PKCS#7 con HSM', [
                'http_code' => $httpCode,
                'error' => $error,
                'cadena_original' => $cadena
            ]);
            throw new \Exception("Error al firmar PKCS#7 con HSM: $error");
        }

        return $response; // base64 sin headers
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



    private function autenticarseEnSat(): string
    {
        $token = "";

        $fecha_inicial = time() - date('Z');
        $fecha_final = $fecha_inicial + (60*5);
        $created = date("Y-m-d\TH:i:s\.v\Z", $fecha_inicial);
        $expires = date("Y-m-d\TH:i:s\.v\Z", $fecha_final);

        $uuid = Str::uuid()->toString();
        $uuid = "uuid-$uuid-1";
        // --- CSD CERTIFICADO -- //
        $certificado = Storage::disk('csd')->get('00001000000710051653.cer');
        $credential = new Certificate($certificado);
        $certificate = self::cleanPemContents($credential->pem());


        $keyInfoData = <<<EOT
            <KeyInfo>
                <o:SecurityTokenReference>
                    <o:Reference URI="#$uuid" ValueType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3"/>
                </o:SecurityTokenReference>
            </KeyInfo>
            EOT;

        $toDigestXml = <<<EOT
            <u:Timestamp xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" u:Id="_0">
                <u:Created>{$created}</u:Created>
                <u:Expires>{$expires}</u:Expires>
            </u:Timestamp>
            EOT;

        $signatureData = self::createSignatureXml($toDigestXml, '#_0', $keyInfoData, $credential);

        $xml = <<<EOT
            <s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
                <s:Header>
                    <o:Security xmlns:o="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" s:mustUnderstand="1">
                        <u:Timestamp u:Id="_0">
                            <u:Created>{$created}</u:Created>
                            <u:Expires>{$expires}</u:Expires>
                        </u:Timestamp>
                        <o:BinarySecurityToken u:Id="$uuid" ValueType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3" EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">
                            $certificate
                        </o:BinarySecurityToken>
                        $signatureData
                    </o:Security>
                </s:Header>
                <s:Body>
                    <Autentica xmlns="http://tempuri.org/" />
                </s:Body>
            </s:Envelope>
            EOT;

        $soapEnvelope = self::nospaces($xml);

        $headers = [
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: "http://tempuri.org/IAutenticacion/Autentica"',
            'Content-Length: ' . strlen($soapEnvelope),
        ];

        $url = 'https://recepcion.facturaelectronica.sat.gob.mx/Seguridad/Autenticacion.svc';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $soapEnvelope,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if (!$response) {
            throw new Exception("Error al autenticar en el SAT: $error");
        }
        if (!preg_match('/<AutenticaResult>(<!\[CDATA\[)?(.*?)(\]\]>)?<\/AutenticaResult>/s', $response, $match)) {
            throw new Exception("Respuesta inválida del SAT: $response");
        }


        // Verifica que no hubo error
        if ($response === false) {
             throw new Exception("Error en la solicitud cURL");
        }

        // Parsear XML
        $xml = simplexml_load_string($response);
        $namespaces = $xml->getNamespaces(true);

        // Navegar al Body
        $body = $xml->children($namespaces['s'])->Body;

        // Extraer AutenticaResult
        $autenticaResponse = $body->children('http://tempuri.org/')->AutenticaResponse;
        $token = (string)$autenticaResponse->AutenticaResult;

        Log::info('Token obtenido del SAT EN EL METODO AUTENTICACION: ', ['token' => $token]);
        if (empty($token)) {
            throw new Exception("Token vacío en la respuesta del SAT");
        }

        return $token;

    }

    /**
     * Enviar el CFDI al SAT al metodo de recepcion de documentos.
     *
     * @param string $token obtenido del login al sat
     * @param Cfdi $cfdi
     * @param string $nameXml nombre del archivo XML
     * @return void
     * @throws Exception
     */
    private function enviarSoapSat(string $token, Cfdi $cfdi, string $nameXml): array
    {

        if(!$token) {
            throw new Exception("Token no válido");
        }

        $emisor = Emisor::where('rfc', $cfdi->emisor->rfc)->first();

        $numeroCertificado = '00001000000710051653';

        if ($emisor && $emisor->path_certificado) {
            $certificate = new \CfdiUtils\Certificado\Certificado($emisor->path_certificado);
            $numeroCertificado = $certificate->getSerial();
        } elseif (!$emisor) {
            Log::warning('Emisor no encontrado para el CFDI', ['rfc' => $cfdi->emisor->rfc]);
        }

        $data = [
            'RfcEmisor' => $cfdi->emisor->rfc,
            'UUID' => $cfdi->uuid,
            'Fecha' => $cfdi->fecha,
            'NumeroCertificado' => $numeroCertificado,
            'VersionComprobante' => "4.0",
            'RutaCFDI' => config('pac.BlobStorageEndpoint') . "asf180914ky5/$nameXml",
        ];

        $fecha = Carbon::parse($cfdi->fecha)->format('Y-m-d\TH:i:s');


        $soapEnvelope = <<<EOT
                <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:rec="http://recibecfdi.sat.gob.mx">
                <soapenv:Header>
                    <rec:EncabezadoCFDI>
                        <rec:RfcEmisor>{$data['RfcEmisor']}</rec:RfcEmisor>
                        <rec:UUID>{$data['UUID']}</rec:UUID>
                        <rec:Fecha>{$fecha}</rec:Fecha>
                        <rec:NumeroCertificado>{$data['NumeroCertificado']}</rec:NumeroCertificado>
                        <rec:VersionComprobante>{$data['VersionComprobante']}</rec:VersionComprobante>
                    </rec:EncabezadoCFDI>
                </soapenv:Header>
                <soapenv:Body>
                    <rec:CFDI>
                        <rec:RutaCFDI>{$data['RutaCFDI']}</rec:RutaCFDI>
                    </rec:CFDI>
                </soapenv:Body>
                </soapenv:Envelope>
                EOT;

        $soapEnvelope = self::nospaces($soapEnvelope);
        $soapEnvelope = stripslashes($soapEnvelope);

        Log::debug('XML de envío al SAT', ['xml' => $soapEnvelope]);

        $url = 'https://recepcion.facturaelectronica.sat.gob.mx/Recepcion/CFDI40/RecibeCFDIService.svc';

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $soapEnvelope,
            CURLOPT_HTTPHEADER => [
                'Content-Type: text/xml; charset=utf-8',
                'Content-Length: ' . strlen($soapEnvelope),
                'SOAPAction: "http://recibecfdi.sat.gob.mx/IRecibeCFDIService/Recibe"',
                'Authorization: WRAP access_token="' . $token .'"',
                'Expect: ' // Importante para evitar problemas con algunos servidores
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT => 45, // Aumentado a 45 segundos
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_FAILONERROR => false, // Para manejar manualmente códigos HTTP
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        ]);

       $response = curl_exec($ch);

        if ($response === false) {
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("Error CURL ($errno) al enviar CFDI al SAT: $error");
        }

        curl_close($ch);

        // Parsear respuesta como XML
        $xmlResponse = simplexml_load_string($response);
        if ($xmlResponse === false) {
            throw new Exception("Respuesta XML inválida del SAT");
        }

        // Registrar respuesta completa para depuración
        Log::debug('Respuesta completa del SAT', ['response' => $response]);

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

        // Validar que el UUID coincida con el enviado
        if (strtoupper($uuid) !== strtoupper($cfdi->uuid)) {
            throw new Exception("El UUID en el acuse no coincide con el CFDI enviado");
        }

        // Determinar estado basado en el código
        $estado = (str_starts_with($codigo, '200') || $codigo === 'Comprobante recibido satisfactoriamente')
            ? 'aceptado'
            : 'rechazado';

        // Actualización del modelo con todos los datos del acuse
        $updateData = [
            'fecha_envio_sat' => now(),
            'fecha_respuesta_sat' => $fechaAcuse,
            'respuesta_sat' => $response, // Guardamos la respuesta completa
            'token_sat' => $token,
            'intento_envio_sat' => $cfdi->intento_envio_sat + 1,
            'estatus' => $estado === 'aceptado' ? 'publicado' : 'rechazado',
            'estado_sat' => $estado,
            'codigo_estatus_sat' => $codigo,
            'mensaje_sat' => $codigo,
            'no_certificado_sat' => $noCertificadoSAT,
            'incidencia_sat' => $incidenciaData ? json_encode($incidenciaData) : null,
        ];

        if (!$cfdi->update($updateData)) {
            throw new Exception("Error al actualizar el CFDI en la base de datos");
        }

        // Log exitoso
        Log::info('CFDI procesado por el SAT', [
            'uuid' => $uuid,
            'estado' => $estado,
            'codigo' => $codigo,
            'mensaje' => $codigo,
            'incidencia' => $incidenciaData
        ]);

        return ['xml' => $xmlResponse->asXML()];
    }

     private function enviarSoapSatFromXml(string $token, Cfdi $cfdi, string $nameXml): void
    {

        if(!$token) {
            throw new Exception("Token no válido");
        }

        $data = [
            'RfcEmisor' => $cfdi->emisor->rfc,
            'UUID' => $cfdi->uuid,
            'Fecha' => $cfdi->fecha,
            'NumeroCertificado' => "00001000000710051653",
            'VersionComprobante' => "4.0",
            'RutaCFDI' => config('pac.BlobStorageEndpoint') . "asf180914ky5/$nameXml",
        ];

        $fecha = Carbon::parse($cfdi->fecha)->format('Y-m-d\TH:i:s');


        $soapEnvelope = <<<EOT
                <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:rec="http://recibecfdi.sat.gob.mx">
                <soapenv:Header>
                    <rec:EncabezadoCFDI>
                        <rec:RfcEmisor>{$data['RfcEmisor']}</rec:RfcEmisor>
                        <rec:UUID>{$data['UUID']}</rec:UUID>
                        <rec:Fecha>{$fecha}</rec:Fecha>
                        <rec:NumeroCertificado>{$data['NumeroCertificado']}</rec:NumeroCertificado>
                        <rec:VersionComprobante>{$data['VersionComprobante']}</rec:VersionComprobante>
                    </rec:EncabezadoCFDI>
                </soapenv:Header>
                <soapenv:Body>
                    <rec:CFDI>
                        <rec:RutaCFDI>{$data['RutaCFDI']}</rec:RutaCFDI>
                    </rec:CFDI>
                </soapenv:Body>
                </soapenv:Envelope>
                EOT;

        $soapEnvelope = self::nospaces($soapEnvelope);
        $soapEnvelope = stripslashes($soapEnvelope);

        Log::debug('XML de envío al SAT', ['xml' => $soapEnvelope]);

        $url = 'https://recepcion.facturaelectronica.sat.gob.mx/Recepcion/CFDI40/RecibeCFDIService.svc';

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $soapEnvelope,
            CURLOPT_HTTPHEADER => [
                'Content-Type: text/xml; charset=utf-8',
                'Content-Length: ' . strlen($soapEnvelope),
                'SOAPAction: "http://recibecfdi.sat.gob.mx/IRecibeCFDIService/Recibe"',
                'Authorization: WRAP access_token="' . $token .'"',
                'Expect: ' // Importante para evitar problemas con algunos servidores
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT => 45, // Aumentado a 45 segundos
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_FAILONERROR => false, // Para manejar manualmente códigos HTTP
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        ]);

       $response = curl_exec($ch);

        if ($response === false) {
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("Error CURL ($errno) al enviar CFDI al SAT: $error");
        }

        curl_close($ch);

        // Parsear respuesta como XML
        $xmlResponse = simplexml_load_string($response);
        if ($xmlResponse === false) {
            throw new Exception("Respuesta XML inválida del SAT");
        }

        // Registrar respuesta completa para depuración
        Log::debug('Respuesta completa del SAT', ['response' => $response]);

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

        // Validar que el UUID coincida con el enviado
        if (strtoupper($uuid) !== strtoupper($cfdi->uuid)) {
            throw new Exception("El UUID en el acuse no coincide con el CFDI enviado");
        }

        // Determinar estado basado en el código
        $estado = (str_starts_with($codigo, '200') || $codigo === 'Comprobante recibido satisfactoriamente')
            ? 'aceptado'
            : 'rechazado';

        // Actualización del modelo con todos los datos del acuse
        $updateData = [
            'fecha_envio_sat' => now(),
            'fecha_respuesta_sat' => $fechaAcuse,
            'respuesta_sat' => $response, // Guardamos la respuesta completa
            'token_sat' => $token,
            'intento_envio_sat' => $cfdi->intento_envio_sat + 1,
            'estado_sat' => $estado,
            'codigo_estatus_sat' => $codigo,
            'mensaje_sat' => $codigo,
            'no_certificado_sat' => $noCertificadoSAT,
            'incidencia_sat' => $incidenciaData ? json_encode($incidenciaData) : null,
        ];

        if (!$cfdi->update($updateData)) {
            throw new Exception("Error al actualizar el CFDI en la base de datos");
        }

        // Log exitoso
        Log::info('CFDI procesado por el SAT', [
            'uuid' => $uuid,
            'estado' => $estado,
            'codigo' => $codigo,
            'mensaje' => $codigo,
            'incidencia' => $incidenciaData
        ]);
    }

   static function procesarRespuestaSAT($xmlResponse) {
        $xml = simplexml_load_string($xmlResponse);

        if (!$xml) {
            throw new Exception("XML de respuesta inválido");
        }

        // Namespaces
        $ns = [
            'soap' => 'http://schemas.xmlsoap.org/soap/envelope/',
            'sat' => 'http://recibecfdi.sat.gob.mx',
            'ds' => 'http://www.w3.org/2000/09/xmldsig#'
        ];

        $body = $xml->children($ns['soap'])->Body;
        if (!$body) {
            throw new Exception("Estructura SOAP inválida - falta Body");
        }

        $acuse = $body->children($ns['sat'])->AcuseRecepcion->AcuseRecepcionCFDI;
        if (!$acuse) {
            throw new Exception("No se encontró el acuse de recepción");
        }

        // Validar atributos obligatorios
        $requiredAttrs = ['UUID', 'CodEstatus', 'Fecha'];
        foreach ($requiredAttrs as $attr) {
            if (!isset($acuse[$attr])) {
                throw new Exception("Falta atributo requerido: $attr");
            }
        }

        return [
            'uuid' => (string)$acuse['UUID'],
            'estatus' => (string)$acuse['CodEstatus'],
            'fecha' => (string)$acuse['Fecha'],
            'certificado_sat' => (string)($acuse['NoCertificadoSAT'] ?? ''),
            'mensaje' => (string)($acuse['Mensaje'] ?? ''),
            'es_valido' => strpos((string)$acuse['CodEstatus'], 'recibido') !== false
        ];
    }


    /**
     * Subir el CFDI a Azure Blob Storage.
     *
     * @param string $uuid
     * @param string $xml
     * @return bool
     */
    private function subirABlob(string $uuid, string $xml): bool
    {
           try {
                $disk = Storage::disk('azure');
                $disk->put("{$uuid}.xml", $xml);


                Log::info('CFDI subido a Azure Blob', [
                    'uuid' => $uuid
                ]);

                return true;

            } catch (\Exception $e) {
                Log::error('Error al subir CFDI a Azure Blob', [
                    'uuid' => $uuid,
                    'error' => $e->getMessage()
                ]);
                throw new Exception("Error al subir CFDI a Azure Blob: {$e->getMessage()}");
            }
    }




    private function createSignedInfoCanonicalExclusive(string $digested, string $uri = ''): string
    {
            $xml = <<<EOT
                <SignedInfo xmlns="http://www.w3.org/2000/09/xmldsig#">
                    <CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"></CanonicalizationMethod>
                    <SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"></SignatureMethod>
                    <Reference URI="$uri">
                        <Transforms>
                            <Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"></Transform>
                        </Transforms>
                        <DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"></DigestMethod>
                        <DigestValue>$digested</DigestValue>
                    </Reference>
                </SignedInfo>
                EOT;

                return self::nospaces($xml);
    }

    private function createSignatureXml(string $toDigest, string $signedInfoUri = '', string $keyInfo = '', $certificate): string
    {

            $hsmUrl = 'http://35.208.215.143/akval-firma/api/FirmaHsm/FirmaCxiSha1';
            $toDigest = self::nospaces($toDigest);
            $digested = base64_encode(sha1($toDigest, true));
            $signedInfo = self::createSignedInfoCanonicalExclusive($digested, $signedInfoUri);
            $shaSign = base64_encode(sha1($signedInfo, true));
            $signatureValue =  TimbradoService::firmarConHSM($hsmUrl,  $shaSign);
            $signedInfo = str_replace('<SignedInfo xmlns="http://www.w3.org/2000/09/xmldsig#">', '<SignedInfo>', $signedInfo);

            return <<<EOT
                <Signature xmlns="http://www.w3.org/2000/09/xmldsig#">
                    $signedInfo
                    <SignatureValue>$signatureValue</SignatureValue>
                    $keyInfo
                </Signature>
                EOT;
    }

    public static function cleanPemContents(string $pemContents): string
    {
            $filteredLines = array_filter(
                explode("\n", $pemContents),
                fn (string $line): bool => ! str_starts_with($line, '-----')
            );
            return implode('', array_map('trim', $filteredLines));
    }




    public static function nospaces(string $input): string
    {
            return preg_replace(
                [
                    '/^\h*/m',      // A: remove horizontal spaces at beginning
                    '/\h*\r?\n/m',  // B: remove horizontal spaces + optional CR + LF
                    '/\?></',       // C: xml definition on its own line
                ],
                [
                    '',             // A: remove
                    '',             // B: remove
                    "?>\n<",        // C: insert LF
                ],
                $input
            ) ?? '';
    }


    /**
     * Método para subir el CFDI y enviarlo al SAT.
     *
     * @param string $xml
     * @param string $uuid
     * @return void
     */
    public function onlyUploadAndSendSat($xml, $uuid)
    {
        $this->subirABlob($uuid, $xml);
        Log::info('CFDI almacenado en Azure Blob', ['uuid' => $uuid]);


        $token = $this->autenticarseEnSat();
        Log::debug('Token recibido del SAT', ['token' => $token]);

        $nameXml = $uuid . '.xml';
        $cfdi = Cfdi::where('uuid', $uuid)->first();

        if (!$cfdi) {
            Log::error('CFDI no encontrado', ['uuid' => $uuid]);
            throw new Exception("CFDI no encontrado con UUID: $uuid");
        }

       $this->enviarSoapSat($token, $cfdi, $nameXml);
        Log::info('CFDI enviado exitosamente al SAT', ['uuid' => $cfdi->uuid]);

    }



     public function enviarXml(Cfdi $cfdi): array
    {
        $response = ['xml' => ''];

        try
        {
            $xml = Storage::disk('local')->get($cfdi->ruta);
            $nameXml = $cfdi->uuid . '.xml';

            Log::info('Iniciando proceso de envío SAT para CFDI', ['uuid' => $cfdi->uuid]);

        // $rfc = config('pac.Rfc');
            $rfc = $cfdi->emisor->rfc;

            $fecha = Carbon::parse($cfdi->fecha)->format('Y-m-d\TH:i:s');
            $cadena = "||{$rfc}|{$fecha}||";

            $cadena = base64_encode(hash('sha256', $cadena, true));

            $sello = $this->firmarCadenaConHsmPkcs7($cadena);
            Log::debug('Sello generado', ['sello' => $sello]);

            $token = $this->autenticarseEnSat();
            Log::debug('Token recibido del SAT', ['token' => $token]);


            $this->subirABlob($cfdi->uuid, $xml);
            Log::info('CFDI almacenado en Azure Blob', ['uuid' => $cfdi->uuid]);

            $response = $this->enviarSoapSat($token, $cfdi, $nameXml);
            Log::info('CFDI enviado exitosamente al SAT', ['uuid' => $cfdi->uuid]);

            return $response;


        }catch (\Exception $e) {
            Log::error('Error al enviar CFDI al SAT', [
                'uuid' => $cfdi->uuid,
                'error' => $e->getMessage()
            ]);

            return $response;
         }


    }
}

