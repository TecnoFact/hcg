<?php

namespace App\Services;

use Exception;

class CfdiSignerService
{
    public function firmarCadena(string $cadenaOriginal, string $keyPath, string $cerPath, string $keyPass = ''): array
    {
        $privateKeyPem = $this->convertirKeyToPem($keyPath, $keyPass);
        if (!$privateKeyPem) {
            throw new \Exception("No se pudo cargar la llave privada.");
        }
    
        if (!openssl_sign($cadenaOriginal, $firma, $privateKeyPem, OPENSSL_ALGO_SHA256)) {
            throw new \Exception("No se pudo firmar la cadena original.");
        }
    
        $sello = base64_encode($firma);
    
        // Convertir .cer DER a PEM para parseo
        $der = file_get_contents($cerPath);
        $pem = "-----BEGIN CERTIFICATE-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END CERTIFICATE-----\n";
    
        $x509 = openssl_x509_parse($pem);
        if (!$x509 || !isset($x509['serialNumberHex'])) {
            throw new \Exception('No se pudo extraer el número de serie hexadecimal del certificado.');
        }
    
        //  CORREGIDO
        $rawSerialHex = $x509['serialNumberHex'];
        $serialAscii = hex2bin($rawSerialHex);
        $serialNumber = preg_replace('/[^0-9]/', '', $serialAscii);
        
        if (strlen($serialNumber) !== 20) {
            throw new \Exception("El número de certificado no tiene exactamente 20 dígitos: {$serialNumber}");
        }
    
        $certificadoBase64 = base64_encode($der);
    
        return [
            'sello' => $sello,
            'certificado' => $certificadoBase64,
            'no_certificado' => $serialNumber,
        ];
    }


    private function convertirKeyToPem(string $keyPath, string $pass): ?string
    {
        $keyContent = file_get_contents($keyPath);
        $pkey = openssl_pkey_get_private($keyContent, $pass);

        if (!$pkey) {
            return null;
        }

        $pem = '';
        openssl_pkey_export($pkey, $pem);
        return $pem;
    }

}
