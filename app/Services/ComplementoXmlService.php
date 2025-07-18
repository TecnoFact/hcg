<?php

namespace App\Services;

use DOMXPath;
use Exception;
use DOMDocument;
use App\Models\Emision;
use Illuminate\Support\Facades\Storage;

class ComplementoXmlService
{
    public function insertarTimbreFiscalDigital(string $xml, string $timbreXml): string
    {
        // Cargar el XML del CFDI
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        $xml = file_get_contents($xml);

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

        // Buscar o crear <cfdi:Complemento>
        $complemento = $xpath->query('cfdi:Complemento', $comprobante)->item(0);
        if (!$complemento) {
            $complemento = $dom->createElementNS('http://www.sat.gob.mx/cfd/4', 'cfdi:Complemento');
            $comprobante->appendChild($complemento);
        }

        // Eliminar timbres previos
        $timbres = $xpath->query('tfd:TimbreFiscalDigital', $complemento);
        foreach ($timbres as $oldTimbre) {
            $complemento->removeChild($oldTimbre);
        }

        // Cargar el TFD firmado exactamente como fue generado
        $timbreDoc = new DOMDocument();
        $timbreDoc->preserveWhiteSpace = false;
        $timbreDoc->formatOutput = false;

        if (!$timbreDoc->loadXML($timbreXml)) {
            throw new Exception("No se pudo cargar el XML del TimbreFiscalDigital");
        }

        // ✅ No eliminar atributos. Se preserva el nodo exactamente como fue firmado.
        $tfdElement = $timbreDoc->documentElement;
        $timbreImportado = $dom->importNode($tfdElement, true);
        $complemento->appendChild($timbreImportado);

        return $dom->saveXML();
    }

    public function buildXmlCfdi(array $datos): string
    {

         $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $cfdi = $doc->createElementNS('http://www.sat.gob.mx/cfd/4', 'cfdi:Comprobante');
        $cfdi->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $cfdi->setAttribute('xsi:schemaLocation', 'http://www.sat.gob.mx/cfd/4 http://www.sat.gob.mx/sitio_internet/cfd/4/cfdv40.xsd');

        $cfdi->setAttribute('Version', '4.0');
        $cfdi->setAttribute('Serie', $datos['serie']);
        $cfdi->setAttribute('Folio', $datos['folio']);
        $cfdi->setAttribute('Fecha', $datos['fecha']);
        $cfdi->setAttribute('FormaPago', $datos['forma_pago']);
        $cfdi->setAttribute('NoCertificado', "");
        $cfdi->setAttribute('SubTotal', number_format($datos['sub_total'], 2, '.', ''));
        $cfdi->setAttribute('Moneda', $datos['moneda']);
        $cfdi->setAttribute('Total', number_format($datos['total'], 2, '.', ''));
        $cfdi->setAttribute('TipoDeComprobante', $datos['tipo_comprobante']);
        $cfdi->setAttribute('MetodoPago', $datos['metodo_pago']);
        $cfdi->setAttribute('LugarExpedicion', $datos['lugar_expedicion']);
        //$cfdi->setAttribute('Exportacion', '01');

        // Emisor
        $emisor = $doc->createElement('cfdi:Emisor');
        $emisor->setAttribute('Rfc', $datos['emisor_rfc']);
        $emisor->setAttribute('Nombre', $datos['emisor_nombre']);
        $emisor->setAttribute('RegimenFiscal', $datos['emisor_regimen_fiscal']);
        $cfdi->appendChild($emisor);

        // Receptor
        $receptor = $doc->createElement('cfdi:Receptor');
        $receptor->setAttribute('Rfc', $datos['receptor_rfc']);
        $receptor->setAttribute('Nombre', $datos['receptor_nombre']);
        $receptor->setAttribute('DomicilioFiscalReceptor', $datos['receptor_domicilio']);
        $receptor->setAttribute('RegimenFiscalReceptor', $datos['receptor_regimen_fiscal']);
        $receptor->setAttribute('UsoCFDI', $datos['receptor_uso_cfdi']);
        $cfdi->appendChild($receptor);

        // Conceptos
        $conceptos = $doc->createElement('cfdi:Conceptos');

        foreach ($datos['detalles'] as $c) {
            $concepto = $doc->createElement('cfdi:Concepto');
            $concepto->setAttribute('ClaveProdServ', $c['clave_prod_serv']);
            $concepto->setAttribute('Cantidad', number_format($c['cantidad'], 6, '.', ''));
            $concepto->setAttribute('ClaveUnidad', $c['clave_unidad']);
            $concepto->setAttribute('Unidad', $c['unidad']);
            $concepto->setAttribute('Descripcion', $c['descripcion']);
            $concepto->setAttribute('ValorUnitario', number_format($c['valor_unitario'], 2, '.', ''));
            $concepto->setAttribute('Importe', number_format($c['importe'], 2, '.', ''));
            //$concepto->setAttribute('ObjetoImp', '01'); // No objeto de impuesto
            $conceptos->appendChild($concepto);
        }

        $cfdi->appendChild($conceptos);

        $doc->appendChild($cfdi);

        return $doc->saveXML();

    }

}
