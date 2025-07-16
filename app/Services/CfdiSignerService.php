<?php

namespace App\Services;

use Exception;
use CfdiUtils\XmlResolver\XmlResolver;

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

    public function getNoCertificado(string $keyPath, string $cerPath, string $keyPass = ''): string
    {
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

        return $serialNumber;
    }

    public function agregarNoCertificado(string $xmlContent, string $noCertificado): string
    {
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xmlContent);

        $comprobante = $dom->getElementsByTagName('Comprobante')->item(0);
        if (!$comprobante) {
            throw new \Exception("No se encontró el nodo Comprobante en el XML.");
        }

        // Insertar atributo
        $comprobante->setAttribute("NoCertificado", $noCertificado);

        return $dom->saveXML();
    }

    public static function getQuickArrayCfdi($cfdi)
    {
        $data = [];
        $data['cfdi33'] = $cfdi->getQuickReader();
        $data['qr_cadena'] = self::getCadenaQr($cfdi);
        $data['tfd_cadena_origen'] = self::getTfdCadenaOrigen($cfdi);

        return $data;
    }

    public static function getCadenaQr($cfdi)
    {
        $comprobante = $cfdi->getNode();
        $parameters = new \CfdiUtils\ConsultaCfdiSat\RequestParameters(
            $comprobante['Version'],
            $comprobante->searchAttribute('cfdi:Emisor', 'Rfc'),
            $comprobante->searchAttribute('cfdi:Receptor', 'Rfc'),
            $comprobante['Total'],
            $comprobante->searchAttribute('cfdi:Complemento', 'tfd:TimbreFiscalDigital', 'UUID'),
            $comprobante['Sello']
        );
        return $parameters->expression();
    }

    public static function getTfdCadenaOrigen($cfdi)
    {
        $tfd = $cfdi->getNode()->searchNode('cfdi:Complemento', 'tfd:TimbreFiscalDigital');
        $tfd_xml_string = \CfdiUtils\Nodes\XmlNodeUtils::nodeToXmlString($tfd);
        //$builder->setXsltBuilder($myXsltBuilder);
        $builder = new \CfdiUtils\TimbreFiscalDigital\TfdCadenaDeOrigen();
        $myLocalResourcePath = '/tmp/sat';
        $resolver = new XmlResolver($myLocalResourcePath);
        $builder->setXmlResolver($resolver);
        return $builder->build($tfd_xml_string);
    }

}
