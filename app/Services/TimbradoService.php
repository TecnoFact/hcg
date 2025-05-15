<?php

namespace App\Services;

use Carbon\Carbon;
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
}
