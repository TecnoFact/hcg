<?php

namespace App\Services;

class CfdiXmlInjectorService
{
    /**
     * Inserta Sello, Certificado y NoCertificado dentro del nodo <cfdi:Comprobante>.
     */
    public function insertarDatosEnXml(string $xmlContent, string $sello, string $certificado, string $noCertificado): string
    {
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xmlContent);

        $comprobante = $dom->getElementsByTagName('Comprobante')->item(0);
        if (!$comprobante) {
            throw new \Exception("No se encontró el nodo Comprobante en el XML.");
        }

        // Insertar atributos
        $comprobante->setAttribute("Sello", $sello);
        $comprobante->setAttribute("Certificado", $certificado);
        $comprobante->setAttribute("NoCertificado", $noCertificado);

        return $dom->saveXML();
    }

}
