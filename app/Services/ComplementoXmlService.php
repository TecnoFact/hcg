<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Exception;

class ComplementoXmlService
{
    public function insertarTimbreFiscalDigital(string $xml, string $timbreXml): string
    {
        // Cargar el XML del CFDI completo
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        if (!$dom->loadXML($xml)) {
            throw new Exception("No se pudo cargar el CFDI XML");
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');
        $xpath->registerNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');

        $comprobante = $xpath->query('/cfdi:Comprobante')->item(0);
        if (!$comprobante) {
            throw new Exception('No se encontró el nodo <cfdi:Comprobante>');
        }

        // Buscar o crear el nodo <cfdi:Complemento>
        $complemento = $xpath->query('cfdi:Complemento', $comprobante)->item(0);
        if (!$complemento) {
            $complemento = $dom->createElementNS('http://www.sat.gob.mx/cfd/4', 'cfdi:Complemento');
            $comprobante->appendChild($complemento);
        }

        // Eliminar cualquier timbre anterior
        $timbres = $xpath->query('.//tfd:TimbreFiscalDigital', $complemento);
        foreach ($timbres as $oldTimbre) {
            $complemento->removeChild($oldTimbre);
        }

        // Cargar el XML del timbre firmado
        $timbreDom = new DOMDocument();
        $timbreDom->preserveWhiteSpace = false;
        $timbreDom->formatOutput = false;

        if (!$timbreDom->loadXML($timbreXml)) {
            throw new Exception("No se pudo cargar el XML del TimbreFiscalDigital");
        }

        $tfdElement = $timbreDom->documentElement;

        // ✅ Eliminar xmlns:xsi y xsi:schemaLocation para evitar conflictos
        if ($tfdElement->hasAttribute('xmlns:xsi')) {
            $tfdElement->removeAttribute('xmlns:xsi');
        }
        if ($tfdElement->hasAttribute('xsi:schemaLocation')) {
            $tfdElement->removeAttribute('xsi:schemaLocation');
        }

        // Importar el nodo firmado tal cual
        $timbreImportado = $dom->importNode($tfdElement, true);
        $complemento->appendChild($timbreImportado);

        return $dom->saveXML();
    }
}


