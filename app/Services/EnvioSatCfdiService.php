<?php

namespace App\Services;

use Carbon\Carbon;
use DOMDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\CfdiArchivo;
use GuzzleHttp\Client;
use Exception;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\Fiel;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\FielRequestBuilder;
use PhpCfdi\SatWsDescargaMasiva\Service;
use PhpCfdi\SatWsDescargaMasiva\WebClient\GuzzleWebClient;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

class EnvioSatCfdiService
{
    public function enviar(CfdiArchivo $cfdi): void
    {
        $xml = Storage::disk('local')->get($cfdi->ruta);

        Log::info('Iniciando proceso de envío SAT para CFDI', ['uuid' => $cfdi->uuid]);

        $rfc = config('pac.Rfc');
        $fecha = Carbon::now()->format('Y-m-d\TH:i:s');
        $cadena = "||{$rfc}|{$fecha}||";

        $cadena = base64_encode(hash('sha256', $cadena, true));

        $sello = $this->firmarCadenaConHsmPkcs7($cadena);
        Log::debug('Sello generado', ['sello' => $sello]);

        $token = $this->autenticarseEnSat();
        Log::debug('Token recibido del SAT', ['token' => $token]);

      //  $this->probarConexionSoap();
      //  $this->probarConexionBlob();

        $this->enviarSoapSat($token, $cfdi, $xml);
        Log::info('CFDI enviado exitosamente al SAT', ['uuid' => $cfdi->uuid]);

        $this->subirABlob($cfdi->uuid, $xml);
        Log::info('CFDI almacenado en Azure Blob', ['uuid' => $cfdi->uuid]);
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

        Log::debug("Respuesta");
        Log::debug($response);


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


    /**
     * Genera el XML de autenticación para el SAT.
     *
     * @return string
     */
    private function generateXml()
    {
        $dateCreated = Carbon::now()->format('Y-m-d\TH:i:s');
        $dateExpires = Carbon::now()->addMinutes(50)->format('Y-m-d\TH:i:s');

        $soapEnvelope = <<<XML
        <?xml version="1.0" encoding="utf-8"?>
        <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                       xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                       xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
                       xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd"
                       >

          <soap:Header>
            <u:Timestamp u:Id="uuid-1">
              <u:Created>{$dateCreated}</u:Created>
              <u:Expires>{$dateExpires}</u:Expires>
            </u:Timestamp>
             <o:Security s:mustUnderstand="1"
            xmlns:o="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
            <!-- Aquí puedes agregar nodos adicionales de seguridad si es necesario -->
            </o:Security>
          </soap:Header>


          <soap:Body>
            <Autentica xmlns="http://tempuri.org/"></Autentica>
          </soap:Body>
        </soap:Envelope>
        XML;

        return $soapEnvelope;

    }


    private function autenticarseEnSat(): string
    {
        $certificado = Storage::disk('certi')->path('certs/00001000000710981021.cer');
        $privateKeyContents = Storage::disk('certi')->path('certs/00001000000710981021.key');
        $passPhrase = 'cPRM2379';

        $token = "";

        $fiel = Fiel::create(
            file_get_contents($certificado),
            file_get_contents($privateKeyContents),
            $passPhrase
        );

        $webClient = new GuzzleWebClient();

        // creación del objeto encargado de crear las solicitudes firmadas usando una FIEL
        $requestBuilder = new FielRequestBuilder($fiel);

        // Creación del servicio
        $service = new Service($requestBuilder, $webClient);

         Log::debug('Token generado DESDE METODO', ['token' => $service->obtainCurrentToken()->getValue()]);

        $token = $service->obtainCurrentToken()->getValue();

        return $token;

    }

    private function enviarSoapSat(string $token, CfdiArchivo $cfdi, string $xml): void
    {
        $url = config('pac.EndpointEnviarCfdi');

        $soapEnvelope = <<<XML
        <?xml version="1.0" encoding="utf-8"?>
        <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                       xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                       xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
          <soap:Body>
            <EnviarCfdi xmlns="http://tempuri.org/">
              <token>{$token}</token>
              <cfdi><![CDATA[{$xml}]]></cfdi>
            </EnviarCfdi>
          </soap:Body>
        </soap:Envelope>
        XML;

        $headers = [
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: "http://tempuri.org/IRecepcion/EnviarCfdi"',
            'Content-Length: ' . strlen($soapEnvelope),
        ];

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
            throw new Exception("Error al enviar CFDI al SAT: $error");
        }

        if (!preg_match('/<EnviarCfdiResult>(<!\[CDATA\[)?(.*?)(\]\]>)?<\/EnviarCfdiResult>/s', $response, $match)) {
            throw new Exception("Respuesta inválida del SAT: $response");
        }

        $resultado = trim($match[2]);

        $acuse = simplexml_load_string($resultado);
        if (!$acuse || !isset($acuse['CodEstatus'])) {
            throw new Exception("Acuse inválido del SAT");
        }

        $codigo = (string) $acuse['CodEstatus'];
        $mensaje = (string) $acuse['Mensaje'] ?? 'Sin mensaje';

        if (!str_starts_with($codigo, '200')) {
            throw new Exception("SAT rechazó el CFDI: [$codigo] $mensaje");
        }

        $cfdi->update([
            'fecha_envio_sat' => now(),
            'respuesta_sat' => $resultado,
            'token_sat' => $token,
            'intento_envio_sat' => $cfdi->intento_envio_sat + 1,
        ]);
    }

    private function probarConexionSoap(): void
    {
        $url = config('pac.EndpointEnviarCfdi');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 400) {
            throw new Exception("No se pudo conectar al servicio SOAP del SAT. HTTP $httpCode");
        }

        Log::debug('Conexión SOAP verificada exitosamente');
    }

    private function probarConexionBlob(): void
    {
        $url = config('pac.BlobStorageEndpoint');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 400) {
            throw new Exception("No se pudo conectar al Azure Blob Storage. HTTP $httpCode");
        }

        Log::debug('Conexión Blob verificada exitosamente');
    }

    private function subirABlob(string $uuid, string $xml): void
    {
        $container = config('pac.ContainerName');
        $sas = config('pac.SharedAccesSignature');
        $baseUrl = config('pac.BlobStorageEndpoint');
        $url = "{$baseUrl}{$container}/{$uuid}.xml{$sas}";

        try {
            $client = new Client();
            $client->put($url, [
                'body' => $xml,
                'headers' => [
                    'x-ms-blob-type' => 'BlockBlob',
                    'Content-Type' => 'application/xml',
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al subir CFDI al Blob Storage', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("Error al subir CFDI al Blob Storage: " . $e->getMessage());
        }
    }
}

