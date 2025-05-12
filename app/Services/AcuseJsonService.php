<?php

namespace App\Services;

class AcuseJsonService
{
    public function generarDesdeXml(string $xml): array
    {
        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');
        $xpath->registerNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');

        $uuid = $xpath->evaluate('string(//tfd:TimbreFiscalDigital/@UUID)');
        $fechaTimbrado = $xpath->evaluate('string(//tfd:TimbreFiscalDigital/@FechaTimbrado)');
        $selloSAT = $xpath->evaluate('string(//tfd:TimbreFiscalDigital/@SelloSAT)');
        $noCertificadoSAT = $xpath->evaluate('string(//tfd:TimbreFiscalDigital/@NoCertificadoSAT)');

        $rfcEmisor = $xpath->evaluate('string(//cfdi:Emisor/@Rfc)');
        $rfcReceptor = $xpath->evaluate('string(//cfdi:Receptor/@Rfc)');
        $total = $xpath->evaluate('string(//cfdi:Comprobante/@Total)');
        $version = $xpath->evaluate('string(//cfdi:Comprobante/@Version)');
        $serie = $xpath->evaluate('string(//cfdi:Comprobante/@Serie)');
        $folio = $xpath->evaluate('string(//cfdi:Comprobante/@Folio)');

        return [
            'uuid' => $uuid,
            'fechaTimbrado' => $fechaTimbrado,
            'rfcEmisor' => $rfcEmisor,
            'rfcReceptor' => $rfcReceptor,
            'total' => $total,
            'version' => $version,
            'serie' => $serie,
            'folio' => $folio,
            'selloSAT' => $selloSAT,
            'noCertificadoSAT' => $noCertificadoSAT,
        ];
    }
}
